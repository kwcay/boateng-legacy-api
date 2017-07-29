<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Traits;

use Storage;
use PharData;
use Exception;
use Illuminate\Console\Command;
use App\Factories\BackupFactory;

trait FileTrait
{
    /**
     * @var $store
     */
    protected $store;

    /**
     * Retrieves a filename from the cli
     *
     * @return null|string
     */
    protected function getFilenameFromAgrs()
    {
        if (! $this->store) {
            $this->error('Invalid storage setup');

            return null;
        }

        // Retriete from argument list.
        $filename = $this->argument('file');

        // List available files on cli.
        if (empty($filename)) {
            $files = array_reverse($this->store->allFiles('/'));
            $filename = $this->choice('Select a file:', $files, 0);
        }

        // Performance check.
        if (! $this->store->exists($filename)) {
            $this->error('Can\'t find file "'.$filename.'".');

            return null;
        }

        return $filename;
    }

    /**
     * Retrieves metadata from a tar file.
     *
     * @param string $filename
     * @return null|array
     */
    public function getPharMetaData($filename)
    {
        try {
            $phar = new PharData($filename);
        } catch (Exception $e) {
            $this->error('Could not open Phar: '. $e->getMessage());

            return null;
        }

        $meta = $phar->getMetaData();
        if (! empty($meta)) {
            return $meta;
        }

        // Try looking inside file
        // ...

        return null;
    }

    /**
     * Creates a unique filename.
     *
     * @param  string $prefix
     * @return string
     */
    public function generateFileName($prefix)
    {
        return $prefix.'_'.gmdate('ymd').'-'.substr(time(), -5).'-'.substr(md5(microtime()), -3);
    }

    /**
     * Creates a GZ-compressed file on disk.
     *
     * @param  string  $path
     * @param  string  $filename
     * @param  string  $contents
     * @return int|false
     */
    public function compressTo($path, $filename, $contents)
    {
        if (! $this->store) {
            $this->error('Invalid storage setup');

            return false;
        }

        // Make sure base directory exists
        if (! $this->store->makeDirectory($path, 0755, true)) {
            $this->error('Could not create storage directory.');

            return false;
        }

        return $this->store->put($path.'/'.$filename.'.gz', gzcompress($contents));
    }
}
