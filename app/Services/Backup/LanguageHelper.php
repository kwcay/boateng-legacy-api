<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

use App\Models\Alphabet;
use App\Models\Language;
use App\Services\Backup\Contract;

class LanguageHelper extends DataImportFactory
{
    /**
     * Stores loaded alphabets.
     */
    private $_alphabets = [];

    /**
     * Imports a data set into the database.
     *
     * @param   array $dataSet
     * @return  App\Services\Backup\LanguageHelper
     */
    public function restore(array $dataSet = null)
    {
        // Loop through languages and import them one by one.
        $saved = $skipped = 0;
        foreach ($dataSet as $langArray) {
            // Performance check.
            if (! array_key_exists('code', $langArray) || Language::codeExists($langArray['code'])) {
                $skipped++;
                continue;
            }

            // Create a language object.
            $lang = Language::create(array_only($langArray, [
                'code',
                'parentCode',
                'name',
                'altNames',
                'createdAt',
                'deletedAt',
            ]));

            // Add alphabets.
            if (array_key_exists('alphabets', $langArray) && is_array($langArray['alphabets'])) {
                // TODO
                // ...
            }

            // Or try to find alphabets for this language.
            elseif ($alphabets = $this->findAlphabets($lang->code)) {
                $lang->alphabets()->attach($alphabets);
            }

            // Add transliterations.
            if (array_key_exists('transliterations', $langArray) && is_array($langArray['transliterations'])) {
                $lang->saveTransliterations($langArray['transliterations']);
            }

            // Add language family.
            if (array_key_exists('family', $langArray)) {
                // TODO
                // ...
            }

            // Add related languages.
            if (array_key_exists('related', $langArray) && is_array($langArray['related'])) {
                // TODO
                // ...
            }

            // Add data.
            if (array_key_exists('data', $langArray) && is_array($langArray['data'])) {
                // TODO...

                // TODO: add contributors...
            }

            // Add media.
            if (array_key_exists('media', $langArray) && is_array($langArray['media'])) {
                // TODO
                // ...
            }

            // Add countries.
            if (array_key_exists('countries', $langArray) && is_array($langArray['countries'])) {
                // TODO
                // ...
            }

            $saved++;
        }

        $this->setMessage($saved.' of '.($saved + $skipped).' languages updated.');

        return $this;
    }

    private function getAlphabet($code)
    {
        // If an alphabet was already retrieved from the database, pull it from the local array.
        if (array_key_exists($this->_alphabets, $code)) {
            return $this->_alphabets[$code];
        }

        // Else, try to fetch it from the database.
        if ($alphabet = Alphabet::where('code', '=', $code)->first()) {
            $this->_alphabets[$code] = $alphabet->id;

            return $alphabet->id;
        }
    }

    /**
     * @param string $langCode
     * @return array
     */
    private function findAlphabets($langCode)
    {
        $found = [];

        // Loop through loaded alphabets first.
        foreach ($this->_alphabets as $alphabetCode => $alphabetId) {
            if (strpos($alphabetCode, $langCode.'-') === 0) {
                $found[] = $alphabetId;
            }
        }

        // Lookup alphabets in the database as well.
        // $more = Alphabet::where('code', 'LIKE', $langCode.'-%')->whereNotIn('id', $found)->lists('id', 'code');
        $more = Alphabet::where('code', 'LIKE', $langCode.'-%')->whereNotIn('id', $found)->pluck('id');
        if (count($more)) {
            $found = array_merge($found, $more->toArray());
            $this->_alphabets = array_merge($this->_alphabets, $more->toArray());
        }

        return $found;
    }
}
