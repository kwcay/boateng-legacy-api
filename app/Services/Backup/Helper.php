<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

use Exception;

class Helper
{
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
            throw new Exception('Model doesn\'t exist');
        }

        $helperName = 'App\\Services\\Backup\\'.ucfirst($resourceName).'Helper';
        if (! class_exists($helperName)) {
            throw new Exception('Backup helper not implemented');
        }

        return new $helperName;
    }
}
