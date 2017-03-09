<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

use App\Models\Language;
use App\Models\Definition;
use App\Services\Backup\Contract;

class DefinitionHelper extends Conract
{
    /**
     * Stores loaded languages.
     *
     * @var array
     */
    private $_languages = [];

    /**
     * Stores loaded tags.
     *
     * @var array
     */
    private $_tags = [];

    /**
     * Imports a data set into the database.
     *
     * @param   array $dataSet
     * @return  App\Services\Backup\DefinitionHelper
     */
    public function restore(array $dataSet = null)
    {
        // Loop through definitions and import them one by one.
        $saved = $skipped = 0;
        foreach ($dataSet as $data) {
            // TODO: check database for duplicates somehow...
            // ...

            // Definition titles
            $titles = $titleData = [];

            if (array_key_exists('titles', $data) && is_array($data['titles'])) {
                $titleData = $data['titles'];
            } elseif (array_key_exists('titlesArray', $data) && is_array($data['titlesArray'])) {
                $titleData = $data['titlesArray'];
            } elseif (array_key_exists('titleList', $data) && is_array($data['titleList'])) {
                $titleData = $data['titleList'];
            }

            // Performance check.
            if (! count($titleData)) {
                $skipped++;
                continue;
            }

            // Retrieve translations.
            $translations = [];
            if (array_key_exists('translationData', $data) && is_array($data['translationData'])) {
                foreach ($data['translationData'] as $langCode => $translation) {
                    $translations[] = new Translation([
                        'language'  => $langCode,
                        'practical' => $translation['practical'],
                        'literal'   => $translation['literal'],
                        'meaning'   => $translation['meaning'],
                    ]);
                }
            }

            // Performance check.
            if (! count($translations)) {
                $skipped++;
                continue;
            }

            // Retrieve languages.
            $languages = $languageData = [];
            if (array_key_exists('language', $data) && is_array($data['language'])) {
                $languageData = $data['language'];
            } elseif (array_key_exists('languageList', $data) && is_array($data['languageList'])) {
                $languageData = $data['languageList'];
            }

            foreach ($languageData as $code => $name) {
                // Check if language has already been loaded.
                if (isset($this->_languages[$code])) {
                    $languages[] = $this->_languages[$code];
                }

                // If not, attempt to retrieve it from the database.
                elseif ($lang = Language::findByCode($code)) {
                    $languages[] = $lang;
                    $this->_languages[$code] = $lang;
                }

                // If the language is not in our database, try to create a record for it.
                elseif ($lang = Language::create(['code' => $code, 'name' => $name])) {
                    $languages[] = $lang;
                    $this->_languages[] = $lang;
                } else {
                    $this->setMessage('Could not add related language "'.$code.'".');
                }
            }

            // Performance check.
            if (! count($languages)) {
                $skipped++;
                continue;
            }

            // Retrieve definition attributes.
            $attributes = [
                'type' => Definition::getTypeConstant($data['type']),
                'sub_type' => Definition::getSubTypeAbbreviation(Definition::getTypeConstant($data['type']), $data['subType']),
                'main_language_code' => array_get($data, 'mainLanguageCode', $languages[0]->code),
                'rating' => array_get($data, 'rating', 1),
                'meta' => array_get($data, 'meta', ''),
                'created_at' => array_get($data, 'createdAt', null),
                'deleted_at' => array_get($data, 'deletedAt', null),
            ];

            if (! strlen($attributes['meta'])) {
                $attributes['meta'] = '{}';
            }

            // Create a definition object and save it right away, so that we can add the
            // relations afterwards.
            $definition = Definition::firstOrCreate($attributes);

            // Add definition titles.
            foreach ($titleData as $title) {
                // $newTitle = new DefinitionTitle(array_only($title, 'title'));

                // $updatedTitle = $definition->titles()->save($newTitle);
                $updatedTitle = $definition->titles()->create(array_only($title, 'title'));

                // Add transliterations.
                if (array_key_exists('transliterations', $title) && count($title['transliterations'])) {
                    $updatedTitle->saveTransliterations($title['transliterations']);
                }

                else if (array_key_exists('transliteration', $title) && is_string($title['transliteration'])) {
                    $updatedTitle->saveTransliterations([$title['transliteration']]);
                }

                // Add alphabets.
                if (array_key_exists('alphabets', $title) && count($title['alphabets'])) {
                    // TODO...
                }

                // Add media.
                if (array_key_exists('media', $title) && count($title['media'])) {
                    // TODO...
                }
            }

            // Add translations.
            $definition->translations()->saveMany($translations);

            // Add related definitions.
            if (array_key_exists('related', $data) && is_array($data['related'])) {
                // TODO ...
                // Probably in a second step, after all the files are loaded...
            }

            // Add data.
            if (array_key_exists('data', $data) && is_array($data['data'])) {
                // TODO ...

                // TODO: add data contributors...
            }

            // Add media.
            if (array_key_exists('media', $data) && is_array($data['media'])) {
                // TODO ...
            }

            // Add tags.
            if (array_key_exists('tagList', $data) && is_array($data['tagList'])) {
                $tags = [];

                foreach ($data['tagList'] as $tag) {
                    if (is_array($tag)) {
                        $tags[] = Tag::firstOrCreate(array_only($tag, [
                            'title',
                            'language'
                        ]))->id;
                    }

                    else {
                        // TODO: default to current locale, instead of assuming "eng"
                        $tags[] = Tag::firstOrCreate([
                            'title' => $tag,
                            'language' => 'eng'
                        ])->id;
                    }
                }

                if (count($tags)) {
                    $definition->tags()->sync($tags);
                }
            }

            // Add languages.
            $definition->languages()->saveMany($languages);

            // Save the main languge.
            if (empty($definition->mainLanguageCode)) {
                $definition->mainLanguageCode = $languages[0]->code;
                $definition->save();
            }

            $saved++;
        }

        $this->setMessage($saved.' of '.($saved + $skipped).' definitions updated.');

        return $this;
    }
}
