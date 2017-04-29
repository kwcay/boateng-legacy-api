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
     * Store a newly created resource in storage.
     *
     * TODO: integrate with API.
     *
     * @return Response
     */
    public function store()
    {
        // Retrieve the language details.
        $data = Request::only(['code', 'parent_code', 'name', 'alt_names', 'countries']);

        // Set return route.
        $return = Request::input('next') == 'continue' ? 'edit' : 'index';

        return $this->save(new Language, $data, $return);
    }

    /**
     * Update the specified resource in storage.
     *
     * TODO: integrate with API.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        // Retrieve the language object.
        if (! $lang = $this->getLanguage($id)) {
            abort(404, 'Can\'t find that languge :( [todo: throw exception]');
        }

        // Retrieve the language details.
        $data = Request::only(['parent_code', 'name', 'alt_names', 'countries']);

        return $this->save($lang, $data, 'index');
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
        abort(501, 'LanguageController::destroy Not Implemented');
    }

    /**
     * Shortcut to create a new language or save an existing one.
     *
     * TODO: integrate with API.
     *
     * @param \App\Models\Language $lang    Language object.
     * @param array $data                   Language details to update.
     * @param string $return                Relative URI to redirect to.
     * @return Response
     */
    public function save($lang, $data, $return)
    {
        // ...
        if (isset($data['countries']) && is_array($data['countries'])) {
            $data['countries'] = implode(',', $data['countries']);
        }

        // Validate input data
        $test = Language::validate($data);
        if ($test->fails()) {
            // Flash input data to session
            Request::flashExcept('_token');

            // Return to form
            $return = $lang->exists ? route('language.edit', ['id' => $lang->getId()]) : route('language.create');

            return redirect($return)->withErrors($test);
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

        // ...
        switch ($return) {
            case 'index':
                $return = $lang->getUri(false);
                break;

            case 'edit':
                $return = route('language.edit', ['code' => $lang->code]);
                break;

            case 'add':
                $return = route('language.create');
                break;
        }

        Session::push('messages', 'The details for <em>'.$lang->name.'</em> were successfully saved, thanks :)');

        return redirect($return);
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
