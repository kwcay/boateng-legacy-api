<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

use App\Models\Reference;
use App\Services\Backup\Contract;

class ReferenceHelper extends Contract
{
    /**
     *
     */
    public function create() {
        throw new \Exception('ReferenceHelper::create not implemented.');
    }

    /**
     * Imports a data set into the database.
     *
     * @param   array $dataSet
     * @return  App\Services\Backup\ReferenceHelper
     */
    public function restore(array $dataSet = null)
    {
        // Loop through dataset and import each model one by one.
        $saved = $skipped = 0;
        foreach ($dataSet as $data) {
            // TODO: performance check.
            // ...

            // Create model.
            $reference = Reference::create(array_only($data, [
                'type',
                'data',
                'string',
                'deletedAt',
            ]));

            $saved++;
        }

        $this->setMessage($saved.' of '.($saved + $skipped).' references added to database.');

        return $this;
    }
}
