<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

use App\Models\Alphabet;
use App\Services\Backup\Contract;

class AlphabetHelper extends Contract
{
    /**
     *
     */
    public function create() {
        throw new \Exception('AlphabetHelper::create not implemented.');
    }

    /**
     * Imports a data set into the database.
     *
     * @param   array $dataSet
     * @return  App\Services\Backup\AlphabetHelper
     */
    public function restore(array $dataSet = null)
    {
        // Loop through dataset and import each model one by one.
        $saved = $skipped = 0;
        foreach ($dataSet as $data) {
            // Performance check.
            if (! array_key_exists('code', $data) || Alphabet::codeExists($data['code'])) {
                $skipped++;
                continue;
            }

            // Create record.
            $alphabet = Alphabet::create(array_only($data, [
                'name',
                'code',
                'scriptCode',
                'letters',
                'createdAt',
                'deletedAt',
            ]));

            // Add transliterations.
            if (array_key_exists('transliterations', $data) && count($data['transliterations'])) {
                $alphabet->saveTransliterations($data['transliterations']);
            }

            else if (array_key_exists('transliteration', $data) && is_string($data['transliteration'])) {
                $alphabet->saveTransliterations([$data['transliteration']]);
            }

            $saved++;
        }

        $this->setMessage($saved.' of '.($saved + $skipped).' alphabets updated.');

        return $this;
    }
}
