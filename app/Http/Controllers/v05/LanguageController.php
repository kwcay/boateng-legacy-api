<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 *
 * @version 0.5
 * @brief   Handles language-related API requests.
 */
namespace App\Http\Controllers\v0_5;

use Lang;
use Session;
use Request;
use Redirect;
use App\Models\Language;
use App\Http\Controllers\v0_5\Controller as BaseController;

class LanguageController extends BaseController
{
    /**
     * @var int
     */
    protected $defaultQueryLimit = 20;

    /**
     * @var array
     */
    protected $supportedOrderColumns = [
        'id'        => 'ID',
        'code'      => 'ISO 639-3 code',
        'name'      => 'Name',
        'createdAt' => 'Created date',
    ];

    /**
     * @var string
     */
    protected $defaultOrderColumn = 'code';

    /**
     * @var string
     */
    protected $defaultOrderDirection = 'asc';

    /**
     * Performs a search based on the given query.
     *
     * @todo   Deprecate query in path.
     * @param  string $query
     * @return \Illuminate\Http\Response
     */
    public function search($query = null)
    {
        $response = parent::search($query);

        if (! is_array($response)) {
            return $response;
        } elseif ($this->request->get('format') === 'compact') {
            return array_pluck($response['results'], 'name', 'code');
        }

        return $response;
    }

    /**
     * Retrieves a language resource.
     *
     * @param  string $id    Either the ISO 639-3 language code or language ID.
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function getWeekly()
    {
        $embedStr   = $this->request->get('embed', '');
        $weekly     = Language::weekly($embedStr);

        if (! $weekly) {
            return response('No Results Found.', 404);
        }

        // Append extra attributes.
        return $weekly->applyEmbedableAttributes($embedStr);
    }

    /**
     * Creates a new language record.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        return $this->save(
            new Language,
            $this->request->only(['code', 'parent_code', 'name', 'alt_names'])
        );
    }

    /**
     * Updates a language record and its relations.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort(501, 'Not Implemented.');
    }

    /**
     * Shortcut to create a new language or save an existing one.
     *
     * @param  \App\Models\Language  $lang
     * @param  array  $data
     * @return \Illuminate\Http\Response
     */
    protected function save($lang, $data)
    {
        // Validate input data
        $validator = Language::validate($data);
        if ($validator->fails()) {
            $this->throwValidationException($this->request, $validator);
        }

        // Update language details.
        $lang->fill($data);
        if (! $lang->save()) {
            return response('Could Not Save Language.', 500);
        }

        // TODO: update relations.

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
