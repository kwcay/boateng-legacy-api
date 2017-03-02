<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Factories\Backup;

class Helper
{
    /**
     * Meta data.
     *
     * @var array
     */
    protected $metaData;

    /**
     * @param array $meta
     */
    public function setDataMeta(array $meta)
    {
        $this->metaData = $meta;
    }

    /**
     *
     */
    public function getHelper($resourceName = null)
    {
        // Check model name
        $resourceName = preg_replace('/[^a-z]/i', '', $resourceName);
        if (! $resourceName || ! strlen($resourceName)) {
            throw new Exception('Trying to import invalid resource');
        }

        // Check class
        $className = 'App\\Models\\'.ucfirst($resourceName);
        if (! class_exists($className)) {
            throw new Exception('Model doesn\t exist');
        }

        $helperName = 'App\\Factories\\Backup\\'.ucfirst($resourceName).'Helper';

        return new $helperName;
    }
}
