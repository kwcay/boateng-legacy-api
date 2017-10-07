<?php

namespace App\Http\Controllers\v0_5;

use Lang;
use Request;
use Validator;
use App\Http\Requests;
use App\Models\Language;
use App\Models\Definition;
use App\Utilities\Locales;
use App\Models\Translation;
use App\Models\DefinitionTitle;
use Illuminate\Validation\Rule;
use App\Models\Definitions\Word;
use App\Models\Definitions\Expression;
use App\Http\Controllers\v0_5\Controller as BaseController;

class DefinitionController extends BaseController
{
    /**
     *
     */
    protected $defaultQueryLimit = 50;

    /**
     *
     */
    protected $supportedOrderColumns = [
        'id'        => 'ID',
        'createdAt' => 'Created date',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return array|\Illuminate\Http\Response
     */
    public function index()
    {
        $langCode = Language::sanitizeCode($this->request->get('language'));

        // Only allow index for a specified language.
        if (! $langCode) {
            return response('Language must be specified.', 400);
        }

        // TODO: restrict by language.
        // ...

        return $this->indexFromBuilder(Definition::query(), ['language']);
    }

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
            $compact = [];

            foreach ($response['results'] as $result) {
                $def = array_only($result->toArray(), ['type', 'subType']);
                $def['titles']          = array_pluck($result['titles'], 'title');
                $def['translations']    = array_pluck($result['translations'], 'practical', 'language');
                $def['languages']       = array_pluck($result['languages'], 'name', 'code');

                $compact[] = $def;
            }

            return $compact;
        }

        return $response;
    }

    /**
     * Returns a definition resource.
     *
     * @todo   Move to parent class.
     * @param  string  $id  Unique ID of definition.
     * @return Definition|\Illuminate\Http\Response
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
     * @param  string $langCode
     * @return Definition|\Illuminate\Http\Response
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
     * @param string $title
     * @return array|\Illuminate\Http\Response
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
     * @param  string $type
     * @return Definition|\Illuminate\Http\Response
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
     * @return Definition|\Illuminate\Http\Response
     */
    public function store()
    {
        // Validate new definition entries
        $this->validate($this->request, [
            'type'          => 'required',
            'titles'        => 'required',
            'languages'     => 'required',
            'translations'  => 'required',
        ]);

        // Instantiate by definition type.
        switch ($this->request->input('type')) {
            case Definition::TYPE_WORD:
                $definition = new Word;
                break;

            case Definition::TYPE_EXPRESSION:
                $definition = new Expression;
                break;

            default:
                return response('Invalid Definition Type.', 400);
        }

        // Create the record in the database.
        return $this->save($definition);
    }

    /**
     * Updates a definition record and its relations.
     *
     * @param  int  $id  Encrypted ID
     * @return Definition|\Illuminate\Http\Response
     */
    public function update($id)
    {
        // Retrieve definition object
        if (! $definition = Definition::find($id)) {
            return response('Definition Not Found.', 404);
        }

        return $this->save($definition);
    }

    /**
     * Shortcut to save a definition model.
     *
     * @param  Definition $definition
     * @return Definition|\Illuminate\Http\Response
     */
    protected function save($definition)
    {
        // Validate incoming data
        $this->validate($this->request, [
            'type'              => [Rule::in(Definition::TYPES)],

            // Titles
            'titles'            => 'array|min:1',
            'titles.*.title'    => 'required|string|min:1',
            'titles.*.script'   => 'string',

            // Languages
            'languages'         => 'array|min:1',
            'languages.*'       => 'exists:languages,code',

            // Translations
            'translations'              => 'array',
            'translations.*.language'   => ['required', Rule::in(Locales::allKeys())],
            'translations.*.practical'  => 'required|string|min:1',
            'translations.*.literal'    => 'string',
            'translations.*.meaning'    => 'string',

            // Tags
            'tags'              => 'array',

            // Related definitions
            'related'           => 'array',
        ]);

        $type = Definition::getTypeConstant($this->request->get('type', $definition->type));

        // TODO: find a way to validate everything at once
        $this->validate($this->request, [
            'subType'  => ['required', Rule::in(array_flip(Definition::SUB_TYPES[$type]))],
        ]);

        // TODO: generate state hash to see if this object had any changes made

        // TODO: wrap everything in a transaction ?
        // https://laravel.com/docs/5.4/database#database-transactions

        // Update definition
        $definition->fill([
            'type'      => $type,
            'sub_type'  => $this->request->get('subType'),
        ]);

        // Add contributor
        $authorId   = $this->request->user()->uniqueId;
        $authors    = isset($definition->meta['authors']) ? (array) $definition->meta['authors'] : [];

        if (! in_array($authorId, $authors)) {
            $authors[] = $authorId;
            $definition->meta = ['authors' => $authors];
        }

        // Main language code
        // TODO: deprecated
        if (! $definition->exists) {
            $definition->setAttribute('rating', 1);
            $definition->setAttribute('main_language_code', array_first($this->request->input('languages')));
        }

        // Save definition
        if (! $definition->save()) {
            return response('Could Not Save Definition [1].', 500);
        }

        // Update titles
        if ($titles = $this->request->get('titles')) {
            $new        = [];
            $existing   = [];

            // New titles
            foreach ($titles as $title) {
                $title = new DefinitionTitle($title);

                $new[$title->getHash()] = $title;
            }

            // Existing titles
            foreach ($definition->titles as $title) {
                $existing[$title->getHash()] = $title;
            }

            // Titles to remove
            if ($titlesToRemove = array_diff_key($existing, $new)) {
                foreach ($titlesToRemove as $title) {
                    $title->delete();
                }
            }

            // Titles to add
            if ($titlesToAdd = array_diff_key($new, $existing)) {
                $definition->titles()->saveMany($titlesToAdd);
            }
        }

        // Update languages
        if ($languageCodes = $this->request->input('languages')) {
            $languageIDs = [];

            foreach ($languageCodes as $langCode) {
                if ($lang = Language::findByCode($langCode)) {
                    $languageIDs[] = $lang->id;
                }
            }

            $definition->languages()->sync($languageIDs);
        }

        // Update translations
        if ($translations = $this->request->get('translations')) {
            $new        = [];
            $existing   = [];

            // New translations
            foreach ($translations as $data) {
                $new[$data['language']] = new Translation($data);
            }

            // Existing translations
            foreach ($definition->translations as $data) {
                $existing[$data->language] = $data;
            }

            // If an existing translation is about to be overriden, remove it.
            if ($translationsToRemove = array_intersect_key($existing, $new)) {
                foreach ($translationsToRemove as $translation) {
                    // TODO: only remove if new translation is different.
                    $translation->delete();
                }
            }

            // Add all new translations
            $definition->translations()->saveMany($new);
        }

        // TODO: Update tags

        // TODO: Update the history of updates.

        return Definition::find($definition->uniqueId);
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
        if (! $definition = Definition::find($id)) {
            return response('Definition does not exist.', 204);
        }

        // TODO: confirm that relations are also deleted.

        return $definition->delete() ? response('Deleted', 200) : response('Not deleted', 500);
    }
}
