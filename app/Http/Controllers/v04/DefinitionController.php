<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 *
 * @version 0.4
 * @brief   Handles definition-related API requests.
 */
namespace App\Http\Controllers\v0_4;

use Auth;
use Lang;
use Request;
use App\Http\Requests;
use App\Models\Language;
use App\Models\Definition;
use App\Models\Translation;
use App\Models\Definitions\Word;
use App\Http\Controllers\Controller;
use App\Models\Definitions\Expression;
// use Frnkly\ControllerTraits\Embedable;
use App\Http\Controllers\v0_4\Controller as BaseController;

class DefinitionController extends BaseController
{
    protected $defaultQueryLimit = 50;

    protected $supportedOrderColumns = [
        'id'        => 'ID',
        'createdAt' => 'Created date',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $langCode = $this->getParam('lang', '');
        $langCode = Language::sanitizeCode($langCode);

        // Only allow index for a specified language.
        if (! $langCode) {
            return response('Language must be specified.', 400);
        }

        // TODO: restrict by language.
        // ...

        return $this->indexFromBuilder(Definition::query(), ['lang']);
    }

    /**
     * Returns a definition resource.
     *
     * @param  string  $id  Unique ID of definition.
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Performance check.
        if (! $id = Definition::decodeId($id)) {
            return response('Invalid Definition ID.', 400);
        }

        // Retrieve definition object
        if (! $definition = Definition::embed(Request::get('embed'))->find($id)) {
            return response('Definition Not Found.', 404);
        }

        $definition->applyEmbedableAttributes(Request::get('embed'));

        return $definition;
    }

    /**
     * Returns a random definition
     *
     * @return \Illuminate\Http\Response
     */
    public function random($langCode = null)
    {
        // TODO: support languages ?

        // TODO: move this to a global scope, applicable to any resource.
        if (! $definition = Definition::embed(Request::get('embed'))->orderByRaw('RAND()')->first()) {
            return response('Definition Not Found.', 404);
        }

        $definition->applyEmbedableAttributes(Request::get('embed'));

        return $definition;
    }

    /**
     * Finds definitions matching a title (exact match).
     *
     * @todo  Review
     *
     * @param string $definitionType
     * @param string $title
     * @return \Illuminate\Http\Response
     */
    public function findByTitle($title)
    {
        // Performance check
        $title = trim(preg_replace('/[\s+]/', ' ', strip_tags($title)));
        if (strlen($title) < 2) {
            return response('Query Too Short.', 400);
        }

        // TODO: filter by definition type.
        // ...

        // List of relations and attributes to append to results.
        $embed = $this->getEmbedArray(
            $this->request->get('embed'),
            Definition::$appendable
        );

        // Lookup definitions with a specific title
        $definitions = Definition::with($embed['relations']->toArray())->whereHas('titles', function($query) use ($title) {
            $query->where('title', $title);
        })->get();

        // Append extra attributes.
        if (count($embed['attributes']) && count($definitions)) {
            foreach ($definitions as $definition) {
                foreach ($embed['attributes'] as $accessor) {
                    $definition->setAttribute($accessor, $definition->$accessor);
                }
            }
        }

        return $definitions ?: response('Definition Not Found.', 404);
    }

    /**
     * Returns the definition of the day.
     *
     * @param string $definitionType
     * @param string $title
     * @return \Illuminate\Http\Response
     */
    public function getDaily($type = null)
    {
        // Get type constant.
        $type = Definition::getTypeConstant($type);

        if (! is_int($type)) {
            return response('Invalid Definition Type.', 400);
        }

        $langCode = $this->request->get('lang', '*');
        $embedStr = $this->request->get('embed', '');

        switch ($type) {
            case Definition::TYPE_WORD:
                $daily = Word::daily($langCode, $embedStr);
                break;

            case Definition::TYPE_EXPRESSION:
                $daily = Expression::daily($langCode, $embedStr);
                break;

            default:
                return response('Definition Type Unsupported.', 501);
        }

        if (! $daily) {
            return response('No Results Found.');
        }

        // Append extra attributes.
        return $daily->applyEmbedableAttributes($embedStr);
    }

    /**
     * Stores a new definition record.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        // Instantiate by definition type.
        switch ($this->request->input('type')) {
            case Definition::TYPE_WORD:
                $definition = new Word;
                break;

            case Definition::TYPE_EXPRESSION:
                $definition = new Expression;
                break;

            default:
                return response('Invalid definition type.', 400);
        }

        $definition->state = Definition::STATE_VISIBLE;

        // Retrieve data for new definition.
        $data = $this->request->only(['title', 'alt_titles', 'sub_type']);

        // Create the record in the database.
        return $this->save($definition, $data);
    }

    /**
     * Updates a definition record and its relations.
     *
     * @param  int $id
     * @throws \Exception
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        // TODO ...

        return response('Not Implemented.', 501);
    }

    /**
     * Shortcut to save a definition model.
     *
     * @param  \App\Models\Definition $definition
     * @param  array $data
     * @return \Illuminate\Http\Response
     */
    protected function save($definition, array $data = [])
    {
        // Validate input data
        $validator = Definition::validate($data);
        if ($validator->fails()) {
            $this->throwValidationException($this->request, $validator);
        }

        // Add definition to database.
        $definition->fill($data);
        if (! $definition->save()) {
            return response('Could Not Save Definition.', 500);
        }

        // Add language relations.
        $languageCodes = $this->request->input('languages');
        if (is_array($languageCodes)) {
            $languageIDs = [];

            foreach ($languageCodes as $langCode) {
                if ($lang = Language::findByCode($langCode)) {
                    $languageIDs[] = $lang->id;
                }
            }

            $definition->languages()->sync($languageIDs);
        }

        // Add translation relations.
        $rawTranslations = $this->request->input('translations');
        if (is_array($rawTranslations)) {
            $translations = [];

            foreach ($rawTranslations as $foreign => $data) {
                $data['language'] = $foreign;
                $translations[] = new Translation($data);
            }

            $definition->translations()->saveMany($translations);
        }

        return $definition;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @throws \Exception
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // TODO ...

        return response('Not Implemented.', 501);
    }
}
