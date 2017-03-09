<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services;

use Phar;
use Artisan;
use Storage;
use PharData;
use Exception;
use App\Services\Contract;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\UploadedFile as File;
use App\Services\Backup\Helper as BackupHelper;

/**
 * TODO:    create restore flag to continue even if a model import fails,
 *          (e.g. --force-continue). When false, backups should revert,
 *          or at least abort unfinished.
 */
class BackupService extends Contract
{
    /**
     * Specifies the number of objects to store per file for each resource. When restoring a
     * backup file, resources will be loaded in the order specified here as well.
     *
     * The following should be imported as relations:
            - Areas
     *      - Data *
     *      - Media
     *      - Tags
     *      - Transliterations
     *      * Somewhat complex, as they have hasMany relations
     *
     * @var array
     */
    protected $resourceLimits = [
        // 'languageFamily'    => 1000,
        'reference'         => 1000,
        'alphabet'          => 500,
        'language'          => 200,
        // 'country'           => 1000,
        // 'culture'           => 500,
        'definition'        => 200,
        // 'definitionTitle'   => 500,
        'user'              => 500,
    ];

    /**
     * @var int
     */
    protected $startTime;

    /**
     * @param Illuminate\Http\Request $request
     */
    public function __construct(Request $request, BackupHelper $helper)
    {
        $this->request  = $request;
        $this->helper   = $helper;

        $this->boot();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->storage      = Storage::disk('backups');
        $this->tempStorage  = Storage::disk('backups-build');

        $this->startTime    = time();
    }

    /**
     * Uploads a backup file.
     *
     * @param   Illuminate\Http\UploadedFile    $file
     * @return  App\Factories\BackupFactory
     * @throws Exception
     */
    public function upload(File $file)
    {
        // Retrieve filename.
        $file       = $this->request->file('file');
        $filename   = $file->getClientOriginalName();

        // Make sure a file with the same name doesn't already exist.
        if ($this->storage->exists($filename)) {
            throw new Exception('Backup file already exists.');
        }

        // Upload file to backups disk.
        $handle = fopen($file->getRealPath(), 'r');
        if (! $this->storage->put($filename, $handle)) {
            fclose($handle);
            throw new Exception('Could not upload backup file.');
        }

        fclose($handle);
        $this->setMessage('Backup file successfully uploaded.');

        return $this;
    }

    /**
     * Restores a backup file.
     *
     * @param   string  $filename
     * @param   array   $options
     * @return  void
     *
     * @throws  Exception
     */
    public function restore($filename, array $options = [])
    {
        // Performance check.
        $file = $this->getPath($filename);
        if (! $this->storage->exists($file)) {
            throw new Exception('Can\'t find backup file "'.$file.'"');
        }

        // Reset start time.
        $this->startTime = time();

        // Run some pre-restore checks & tasks.
        $this->msg('Putting app in maintenance mode...');
        Artisan::call('down');

        // Unpack backup file.
        $restoreId = 'restore-'.date('Ymd').'-'.substr(md5(microtime()), 20);
        if (! $this->tempStorage->makeDirectory($restoreId)) {
            throw new Exception('Could not create temp directory to unpack backup file.');
        }

        if (! $this->tempStorage->put($restoreId.'/data.tar.gz', $this->storage->get($file))) {
            $this->tempStorage->deleteDirectory($restoreId);

            throw new Exception('Could not copy backup file to temp directory.');
        }

        // Extract backup file.
        $phar = new PharData($this->getDirName($restoreId).'/data.tar.gz');
        try {
            $phar->decompress();
            $phar->extractTo($this->getDirName($restoreId));
        } catch (Exception $e) {
            unset($phar);
            Phar::unlinkArchive($this->getDirName($restoreId).'/data.tar.gz');
            $this->tempStorage->deleteDirectory($restoreId);

            throw new Exception($e->getMessage());
        }

        // Retrieve meta data.
        $meta = $phar->getMetaData();
        if (empty($meta)) {
            if (! $this->tempStorage->exists($restoreId.'/meta.yaml')) {
                unset($phar);
                Phar::unlinkArchive($this->getDirName($restoreId).'/data.tar');
                Phar::unlinkArchive($this->getDirName($restoreId).'/data.tar.gz');
                $this->tempStorage->deleteDirectory($restoreId);

                throw new Exception('Could not find metadata for backup file.');
            }

            $meta = Yaml::parse($this->tempStorage->get($restoreId.'/meta.yaml'));
        }

        // Remove phar files.
        unset($phar);
        Phar::unlinkArchive($this->getDirName($restoreId).'/data.tar');
        Phar::unlinkArchive($this->getDirName($restoreId).'/data.tar.gz');

        // Refresh database.
        if (true === $options['refresh-db']) {
            $this->msg('Refreshing migrations...');

            try {
                Artisan::call('migrate:refresh', ['--force' => true]);
            } catch (Exception $e) {
                $this->tempStorage->deleteDirectory($restoreId);

                throw new Exception($e->getMessage());
            }
        }

        // Restore backup.
        foreach ($this->resourceLimits as $resource => $limit) {
            // Performance check.
            if (! isset($meta[$resource]) || $meta[$resource]['files'] < 1) {
                continue;
            }

            $this->msg("Loading {$meta[$resource]['files']} {$resource} files...");

            try {
                $backupHelper = $this->helper->getHelper($resource);
            } catch (Exception $e) {
                // TODO: handle resource not found.

                $this->msg($e->getMessage());
                $this->msg("Skipping {$resource}");

                continue;
            }
            // TODO: handle other errors

            // Loop through each file and import data.
            for ($i = 0; $i < $meta[$resource]['files']; $i++) {
                $dataFile = "{$restoreId}/{$resource}-{$i}.{$meta['format']}";

                if (! $this->tempStorage->exists($dataFile)) {
                    continue;
                }

                // Retrieve raw data.
                $data = $this->tempStorage->get($dataFile);
                $data = $meta['format'] == 'yaml' ? Yaml::parse($data) : json_decode($data, true);

                // File checksum.
                if (! isset($options['skipChecksum']) || ! $options['skipChecksum']) {
                    if (! $this->integrityCheck($data, $meta[$resource]['checksums'][$i], $meta['checksum-method'])) {
                        $this->tempStorage->deleteDirectory($restoreId);

                        throw new Exception(
                            "Checksum failed for {$resource}-{$i}.{$meta['format']} (expected \"".
                            $meta[$resource]['checksums'][$i].'", got "'.
                            $this->checksum($data, $meta['checksum-method']).'")'
                        );
                    }
                }

                // Import data.
                try {
                    $results = $backupHelper->restore($data);

                    foreach ($results->getMessages() as $msg) {
                        $this->msg("File #$i: {$msg}");
                    }
                } catch (Exception $e) {
                    $this->tempStorage->deleteDirectory($restoreId);

                    throw new Exception($e->getMessage().' in '.$e->getFile().' on line '.$e->getLine());
                }
            }
        }

        // Delete temporary folder.
        $this->tempStorage->deleteDirectory($restoreId);

        // Last checks & tasks.
        Artisan::call('up');

        return $this;
    }

    /**
     * Deletes a backup file.
     *
     * @param   string  $filename
     * @param   int     $timestamp
     *
     * @todo    Restrict access based on roles.
     */
    public function delete($filename, $timestamp = null)
    {
        // Delete backup file.
        if (! $this->storage->delete($this->getPath($filename, $timestamp))) {
            throw new Exception('Couldn\'t delete backup file "'.$this->getPath($filename, $timestamp).'".');
        }

        $this->setMessage('Backup file successfully deleted.');

        return $this;
    }

    /**
     * Calculates a checksum.
     *
     * @todo Mode to some kind of utility or helper
     */
    public function integrityCheck($data, $expected, $method = 'json-md5')
    {
        if (! $data || empty($data)) {
            return false;
        }

        switch ($method)
        {
            case 'json-md5':
                return md5(json_encode($data));
        }

        return false;
    }

    /**
     * Retrieves the relative path of a backup file.
     *
     * @param   string  $filename
     * @throws  Exception
     */
    public function getPath($filename)
    {
        $fullName = null;

        $files = $this->storage->allFiles('/');

        foreach ($files as $file) {
            if (strpos($file, $filename) !== false) {
                $fullName = $file;
            }
        }

        // Make sure a file was found.
        if (! $fullName) {
            throw new Exception('Backup file not found.');
        }

        return $fullName;
    }

    /**
     * Retrieves the full path to a temporary directory.
     *
     * @return string
     */
    protected function getDirName($folder = '')
    {
        return config('filesystems.disks.backups-build.root').'/'.$folder;
    }

    /**
     * @param   string  $msg
     */
    protected function msg($msg = '')
    {
        // Prepend elapsed time.
        $time = '';
        $sec = time() - $this->startTime;
        if ($sec < 60) {
            $time = $sec.' sec';
        } else {
            $mins = floor($sec / 60);
            $time = $mins.' mins '.($sec - $mins * 60).' sec';
        }
        echo "[$time]";

        // Print message.
        if (strlen($msg)) {
            echo " $msg";
        }

        // Newline character.
        echo "\n";
    }
}
