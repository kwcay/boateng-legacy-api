<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

use App\Models\Culture;
use App\Services\Backup\Contract;

class CultureHelper extends Contract
{
    /**
     *
     */
    public function create() {
        throw new \Exception('CultureHelper::create not implemented.');
    }

    /**
     * Imports a data set into the database.
     *
     * @param   array $dataSet
     * @return  App\Services\Backup\CultureHelper
     */
    public function restore(array $dataSet = null)
    {
        // Loop through dataset and import each model one by one.
        $saved = $skipped = 0;
        foreach ($dataSet as $array) {
            // TODO: performance check.
            // ...

            // Create model.
            $culture = Culture::create(array_only($array, [
                'name',
                'altNames',
                'createdAt',
                'deletedAt',
            ]));

            // Add transliterations.
            if (array_key_exists('transliterations', $array) && is_array($array['transliterations'])) {
                $culture->saveTransliterations($array['transliterations']);
            }

            // TODO: languages.
            // TODO: countries.
            // TODO: media.
            // TODO: data.
                // TODO: add data contributors...

            $saved++;
        }

        $this->setMessage($saved.' of '.($saved + $skipped).' cultures updated.');

        return $this;
    }
}
