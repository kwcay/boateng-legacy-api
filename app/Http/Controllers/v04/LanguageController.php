<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 *
 * @version 0.4
 * @brief   Handles language-related API requests.
 */
namespace App\Http\Controllers\v0_4;

use Lang;
use Session;
use Request;
use Redirect;
use App\Models\Language;
use App\Http\Controllers\v0_4\Controller as BaseController;

class LanguageController extends BaseController
{
    protected $defaultQueryLimit = 20;

    protected $supportedOrderColumns = [
        'id'        => 'ID',
        'code'      => 'ISO 639-3 code',
        'name'      => 'Name',
        'createdAt' => 'Created date',
    ];

    protected $defaultOrderColumn = 'code';

    protected $defaultOrderDirection = 'asc';

    /**
     * Retrieves a language resource.
     *
     * @param string $id    Either the ISO 639-3 language code or language ID.
     * @return Response
     */
    public function show($id)
    {
        // Retrieve list of relations and attributes to append to results.
        $embed = $this->getEmbedArray(
            Request::get('embed'),
            Language::$appendable
        );

        // Retrieve the language object.
        if (! $lang = $this->getLanguage($id, $embed['relations']->toArray())) {
            return response('Language Not Found.', 404);
        }

        // Append extra attributes.
        if (count($embed['attributes'])) {
            foreach ($embed['attributes'] as $accessor) {
                $lang->setAttribute($accessor, $lang->$accessor);
            }
        }

        return $lang;
    }

    /**
     * Language of the week.
     *
     * @return Response
     */
    public function getWeekly()
    {
        $embedStr   = $this->request->get('embed', '');
        $weekly     = Language::weekly($embedStr);

        if (! $weekly) {
            return response('No Results Found.');
        }

        // Append extra attributes.
        return $weekly->applyEmbedableAttributes($embedStr);
    }

    /**
     * Creates a new language record.
     *
     * @return Response
     */
    public function store()
    {
        return $this->save(
            new Language,
            $this->request->only(['code', 'parent_code', 'name', 'alt_names'])
        );
    }

    /**
     * Updates a language record and it's relations.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        // Retrieve the language object.
        if (! $lang = $this->getLanguage($id)) {
            abort(404, 'Can\'t find that language :/');
        }

        return $this->save(
            $lang,
            $this->request->only(['parent_code', 'name', 'alt_names'])
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * TODO: integrate with API.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        abort(501, 'Not Implemented.');
    }

    /**
     * Shortcut to create a new language or save an existing one.
     *
     * TODO: integrate with API.
     *
     * @param  \App\Models\Language $lang
     * @param  array $data
     * @return Response
     */
    public function save($lang, $data)
    {
        // Validate input data
        $validator = Language::validate($data);
        if ($validator->fails()) {
            $this->throwValidationException($this->request, $validator);
        }

        // Parent language details
        if (strlen($data['parent_code']) >= 3 && $parent = Language::findByCode($data['parent_code'])) {
            $lang->setParam('parentName', $parent->name);
        } else {
            $data['parent_code'] = '';
            $lang->setParam('parentName', '');
        }

        // Update language details.
        $lang->fill($data);
        $lang->save();

        return $lang;
    }

    /**
     * Shortcut to retrieve a language object.
     *
     * @param string $id    Either the ISO 639-3 language code or language ID.
     * @param array $embed  Database relations to pre-load.
     * @return \App\Models\Language|null
     */
    private function getLanguage($id, array $embed = [])
    {
        // Performance check.
        if (empty($id) || ! is_string($id)) {
            return null;
        }

        // Find language by code.
        if (Language::isValidCode($id)) {
            $lang = Language::findByCode($id, $embed);
        }

        // Or find language by ID.
        else {
            if (! $id = Language::decodeId($id)) {
                return null;
            }

            $lang = Language::with($embed)->find($id);
        }

        return $lang;
    }
}
