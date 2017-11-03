<?php

namespace App\Http\Controllers\v05;

use App\Models\Language;
use App\Http\Controllers\v05\Controller as BaseController;
use Illuminate\Validation\Rule;

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
     * @return array|\Illuminate\Http\Response
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
     * @return Language|array|\Illuminate\Http\Response
     */
    public function show($id)
    {
        // Retrieve list of relations and attributes to append to results.
        $embed = $this->getEmbedArray(
            $this->request->get('embed'),
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
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Http\Response
     */
    public function getWeekly()
    {
        $embed  = $this->request->get('embed', '');
        $weekly = Language::weekly($embed);

        if (! $weekly) {
            return response('No Results Found.', 404);
        }

        // Append extra attributes.
        return $weekly->applyEmbedableAttributes($embed);
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
        return response('Not Implemented.', 501);
    }

    /**
     * Shortcut to create a new language or save an existing one.
     *
     * @param  Language $lang
     * @param  array    $data
     * @return Language|\Illuminate\Http\Response
     */
    protected function save($lang, $data)
    {
        // Validate input data
        $this->validate($this->request, [
            'code' => [
                'sometimes',
                'required',
                'min:3',
                'max:7',
                Rule::unique('languages')->ignore($lang->id)
            ],
            'parent_code' => 'nullable|min:3|max:7',
            'name' => 'required|min:2',
            'alt_names' => 'nullable|min:2',
        ]);

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
     * @return Language|null
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
