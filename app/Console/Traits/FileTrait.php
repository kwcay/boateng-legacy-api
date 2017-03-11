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
     * @var $storage
     */
    protected $storage;

    /**
     * Retrieves a filename from the cli
     *
     * @return null|string
     */
    protected function getFilenameFromAgrs()
    {
        if (! $this->storage) {
            $this->error('Invalid storage setup');

            return null;
        }

        // Retriete from argument list.
        $filename = $this->argument('file');

        // List available files on cli.
        if (empty($filename)) {
            $files = array_reverse($this->storage->allFiles('/'));
            $filename = $this->choice('Select a file:', $files, 0);
        }

        // Performance check.
        if (! $this->storage->exists($filename)) {
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
}
