<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
namespace App\Services\Backup;

use App\Models\LanguageFamily;
use App\Services\Backup\Contract;

class LanguageFamilyHelper extends Contract
{
    /**
     * Imports a data set into the database.
     *
     * @param   array $dataSet
     * @return  App\Services\Backup\LanguageFamilyHelper
     */
    public function restore(array $dataSet = null)
    {
        // Loop through languages and import them one by one.
        $saved = $skipped = 0;
        foreach ($dataSet as $langArray) {
            // TODO: Performance check.
            // ...

            // Create a language object.
            // $lang = Language::create(array_only($langArray, [
            //     'code',
            //     'parentCode',
            //     'name',
            //     'altNames',
            //     'createdAt',
            //     'deletedAt',
            // ]));

            $saved++;
        }

        $this->setMessage($saved.' of '.($saved + $skipped).' language families updated.');

        return $this;
    }
}
