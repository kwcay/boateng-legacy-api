<?php
/**
 * Copyright Dora Boateng(TM) 2015, all rights reserved.
 */
namespace App\Models;

use DB;
use Cache;
use Carbon\Carbon;
use Illuminate\Support\Arr;
// use cebe\markdown\MarkdownExtra;
use App\Models\Definitions\Poem;
use App\Models\Definitions\Word;
use App\Models\Definitions\Expression;
use App\Models\Definitions\Story;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\EmbedableTrait as Embedable;
use App\Traits\HasParamsTrait as HasParams;
use App\Traits\ExportableTrait as Exportable;
use App\Traits\SearchableTrait as Searchable;
use App\Traits\ObfuscatableTrait as Obfuscatable;
use App\Traits\CamelCaseAttributesTrait as CamelCaseAttrs;

class Definition extends Model
{
    use CamelCaseAttrs, Embedable, Exportable, Obfuscatable, Searchable, SoftDeletes, HasParams;


    //
    //
    // Attributes for App\Traits\EmbedableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Attributes that CAN be appended to the model's array form and which aren't already
     * database relations.
     */
    public $embedable = [
        'mainTitle'         => ['titles'],
        'titleString'       => ['titles'],
        'titleList'         => ['titles'],
        'relatedDefinitionList' => ['relatedDefinitions'],
        'tagList'           => ['tags'],
        'translationData'   => ['translations'],
        'allTranslations'   => ['translations'],
        'mainLanguage'      => ['languages'],
        'languageList'      => ['languages'],
        'referenceList'     => ['translations'],
    ];


    //
    //
    // Attributes for App\Traits\ExportableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Supported export formats.
     */
    public $exportFormats = ['yml', 'yaml', 'json', 'bgl', 'dict'];

    /**
     * Attributes that should be hidden when exporting data to file.
     *
     * @var array
     */
    protected $hiddenOnExport = [
        'id',
        'relatedDefinitions',
        'uniqueId',
        'resourceType',
    ];

    /**
     * Attributes that should be appended when exporting data to file.
     *
     * @var array
     */
    protected $appendsOnExport = [
        'titleList',
        'tagList',
        'translationData',
        'languageList',
        'relatedDefinitionList',
    ];


    //
    //
    // Attributes for App\Traits\SearchableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    const SEARCH_LIMIT = 100;       // Maximum number of results to return on a search.
    const SEARCH_QUERY_LENGTH = 1;  // Minimum length of search query.

    /**
     * Indicates whether search results can be filtered by tags.
     *
     * @var bool
     */
    public static $searchIsTaggable = true;


    //
    //
    // Main attributes
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    const TYPE_WORD = 0;        // Regular definitions.
    const TYPE_NAME = 5;        // Proper names.
    const TYPE_EXPRESSION = 10;     // Proverbs, sayings, etc.
    const TYPE_STORY = 30;      // Short stories, poems, songs, etc.

    const STATE_HIDDEN = 0;     // Hidden definition.
    const STATE_VISIBLE = 10;   // Default state.

    const RATING_DEFAULT = 1;       // Default rating.
    const RATING_AUTHENTICATED = 3; // Rating for definitions added by an authenticated user.
    const RATING_HAS_LITERAL = 5;   // Rating for definitions with literal translations.
    const RATING_FULL_TRANS = 10;   // Rating for definitions with literal translations & meanings.

    /**
     * Definition types.
     */
    public $types = [
        self::TYPE_WORD         => 'word',
        self::TYPE_NAME         => 'name',
        self::TYPE_EXPRESSION   => 'expression',
        self::TYPE_STORY        => 'story',
    ];

    /**
     * Definition sub-types.
     */
    public $subTypes = [

        self::TYPE_WORD => [
            // Parts of speech.
            // See: http://www.edb.utexas.edu/minliu/pbl/ESOL/help/libry/speech.htm
            // See: http://www.aims.edu/student/online-writing-lab/grammar/parts-of-speech
            'adj'   => 'adjective',
            'adv'   => 'adverb',
            'conn'  => 'connective',
            'ex'    => 'exclamation',
            'pre'   => 'preposition',
            'pro'   => 'pronoun',
            'n'     => 'noun',
            'v'     => 'verb',
            'intv'  => 'intransitive verb',

            // Morphemes
            'prefix'     => 'prefix',
            'suffix'     => 'suffix',
        ],

        // Types of proper names.
        self::TYPE_NAME => [
            'person'    => 'person',
            'place'     => 'place',
            'name'      => 'other proper name',
        ],

        // Types of phrases.
        self::TYPE_EXPRESSION => [
            'expression' => 'common expression',
            'phrase'    => 'simple phrase',
            'proverb'   => 'proverb or saying',
        ],

        // Types of stories
        self::TYPE_STORY => [
            'poem'  => 'poem',
            'story'  => 'short story',
            'song'  => 'song',
        ],
    ];

    /*
     * Defaults for definition sub types.
     */
    public $defaultSubTypes = [
        self::TYPE_WORD         => 'n',
        self::TYPE_NAME         => 'name',
        self::TYPE_EXPRESSION   => 'expression',
        self::TYPE_STORY        => 'story',
    ];

    /**
     * Rating of definition.
     *
     * @todo Define how to use this
     */
    public $ratings = [
        0 => 'hidden',
        1 => 'unrated',
        5 => 'reviewed',
    ];

    /**
     * The Markdown parser.
     */
    protected $markdown;

    /**
     * Associated MySQL table.
     */
    protected $table = 'definitions';

    /**
     * Attributes which aren't mass-assignable.
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden from the model's array form.
     */
    protected $hidden = [
        'id',
        'titles',
        'relatedDefinitions',
        'related_definitions',
        'tags',
        'languages',
        'translations',
        'deletedAt',
        'deleted_at',
    ];

    /**
     * Attributes that SHOULD be appended to the model's array form.
     */
    protected $appends = [
        'subType',
        'uniqueId',
        'resourceType',
    ];

    /**
     * Attributes that CAN be appended to the model's array form.
     */
    public static $appendable = [
        'mainTitle',
        'titleString',
        'titleList',
        'relatedDefinitionList',
        'tagList',
        'translation',
        'translationData',
        'mainLanguage',
        'languageList',
    ];

    /**
     * Attributes that should be mutated to dates.
     */
    protected $dates = ['deleted_at'];

    /**
     * Attributes that should be cast when assigned.
     */
    protected $casts = [
        'title' => 'string',
        'type' => 'integer',
        'related_definitions' => 'array',
        'meta' => 'array',
    ];


    //
    //
    // Relations
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Defines relation to Translation model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany('App\Models\Translation', 'definition_id');
    }

    /**
     * Defines relation to DefinitionTitle model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function titles()
    {
        return $this->hasMany('App\Models\DefinitionTitle', 'definition_id');
    }

    /**
     * Defines relation to Language model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function languages()
    {
        return $this->belongsToMany('App\Models\Language', 'definition_language', 'definition_id', 'language_id');
    }

    /**
     * Defines relation to Tag model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag', 'definition_tag', 'definition_id', 'tag_id');
    }

    /**
     * Relations to be created when importing this definition.
     */
    protected $relationsToBeImported = [];


    //
    //
    // Helper methods
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Creates an instance of a definition type.
     *
     * @param int $type
     * @param array $attributes
     * @param bool $exists
     * @return App\Models\Definition|null
     */
    public static function getInstance($type, array $attributes = [], $exists = false)
    {
        // Return a general instance.
        if ($type == 'Definition' || $type == 'App\\Models\\Definition') {
            return new self($attributes, $exists);
        }

        // Check that the definition type is valid.
        $types = static::types();
        if (! array_key_exists($type, $types)) {
            return;
        }

        // Return new instance.
        $className = '\\App\\Models\\Definitions\\'.ucfirst(strtolower($types[$type]));

        return new $className($attributes, $exists);
    }

    /**
     * Gets the valid definition types.
     *
     * @return array
     */
    public static function types()
    {
        return (new static)->types;
    }

    /**
     * Returns the name of a definition type.
     *
     * @param int $type
     * @return string
     */
    public static function getTypeName($type)
    {
        return (new self)->types[$type];
    }

    /**
     * Returns the constant value of a definition type.
     *
     * @param string $type
     * @return int|mixed
     */
    public static function getTypeConstant($type, $default = null)
    {
        switch (strtolower(trim($type))) {
            case 'word':
                return static::TYPE_WORD;

            case 'expression':
                return static::TYPE_EXPRESSION;

            case 'story':
                return static::TYPE_STORY;
        }

        return $default;
    }

    /**
     * Returns the constant value of a definition sub-type.
     *
     * @param int|string $type  Type name or type constant
     * @param string $subType   Sub-type name or sub-type constant
     * @return string|mixed     Sub-type constant or $default
     */
    public static function getSubTypeConstant($type, $subType, $default = null)
    {
        $d = new static;

        // Make sure we have a type constant.
        if (! is_numeric($type)) {
            $type = static::getTypeConstant($type);
        }

        if (! isset($d->types[$type])) {
            return $default;
        }

        // Check sub-type keys.
        $subType = strtolower(trim($subType));
        if (isset($d->subTypes[$type][$subType])) {
            return $subType;
        }

        // Check sub-type values.
        $values = array_flip($d->subTypes[$type]);
        if (isset($values[$subType])) {
            return $values[$subType];
        }

        return $default;
    }

    /**
     * Determines whether a definition type is valid or not.
     *
     * @param int|string $type
     * @return int|null
     */
    public static function isValidType($type)
    {
        $types = static::types();

        return array_key_exists($type, $types) || in_array($type, $types);
    }

    /**
     * Gets the list of sub types for this definition.
     *
     * @return array
     */
    public function getSubTypes()
    {
        // return $this->subTypes[$this->getAttributeFromArray('type')];
        return $this->subTypes[$this->rawType];
    }

    /**
     * Gets the abbreviation for a sub type.
     *
     * @param int|string $type
     */
    public static function getSubTypeAbbreviation($type, $subType)
    {
        // Make sure we have a type constant.
        $type = is_numeric($type) ? (int) $type : static::getTypeConstant($type);

        // Find the sub type abbreviation.
        foreach ((new static)->subTypes as $abbr => $sub) {
            if ($sub == $subType) {
                return $abbr;
            }
        }

        // If no sub type was found, return a default value.
        return (new static)->defaultSubTypes[$type];
    }

    /**
     * Gets a random definition.
     *
     * @param App\Models\Language $lang
     * @return App\Models\Definition
     */
    public static function random($lang = null)
    {
        // Get query builder.
        $query = $lang instanceof Language ? $lang->definitions() : static::query();

        // Return a random definition.
        return $query->with('languages', 'translations', 'titles')->orderByRaw('RAND()')->first();
    }

    /**
     * Retrieves definition of the day.
     *
     * @param int       $type
     * @param string    $lang
     * @param string    $relations
     * @return App\Models\Definition
     */
    public static function dailyByType($type, $lang = '*', $embed = '', $subType = '')
    {
        // TODO: throw error.
        if (! is_int($type) || ! static::isValidType($type)) {
            return null;
        }

        // Cache the ID, and let it expire at midnight UTC every day.
        $subType    = static::getSubTypeConstant($type, $subType);
        $cacheKey   = 'definitions.'.$type.'.'.($subType ? $subType : '-').'.daily.'.$lang;
        $expires    = Carbon::now()->diffInMinutes(Carbon::tomorrow());

        $id = Cache::remember($cacheKey, $expires, function () use ($lang, $type, $subType) {
            $lang = Language::findByCode($lang);

            // Get query builder.
            $builder = $lang instanceof Language ? $lang->definitions() : static::query();

            if ($subType) {
                $builder->where('sub_type', $subType);
            }

            // Return a random definition.
            return $builder
                ->where('type', $type)
                ->orderByRaw('RAND()')
                ->value('id');
        });

        if (! $id) {
            return null;
        }

        return static::embed($embed)->find($id);
    }


    //
    //
    // Methods for App\Traits\SearchableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @param string $term      Search query.
     * @param array $options    Search options.
     * @return Builder
     */
    protected static function getSearchQueryBuilder($term, array $options = [])
    {
        $builder = DB::table('definitions AS d')

            // Join the titles table so we can search its columns.
            ->leftJoin('definition_titles AS i', 'i.definition_id', '=', 'd.id')

            // Join the translations table so we can search its columns.
            ->leftJoin('translations AS t', 't.definition_id', '=', 'd.id')

            // Create temporary score columns so we can sort the IDs.
            ->selectRaw(
                'd.id, '.
                'i.title = ? AS title_score, '.
                'i.title LIKE ? AS title_score_low, '.
                'MATCH(t.practical) AGAINST(?) AS practical_score, '.
                't.practical LIKE ? AS practical_score_low, '.
                't.practical = ? AS practical_score_multiplier, '.
                'MATCH(t.literal) AGAINST(?) AS literal_score, '.
                'MATCH(t.meaning) AGAINST(?) AS meaning_score ',
                [$term, '%'.$term.'%', $term, '%'.$term.'%', $term, $term, $term]
            )

            // Try to search in a relevant way.
            ->whereRaw(
                '('.
                    'i.title = ? OR '.
                    'i.title LIKE ? OR '.
                    'MATCH(t.practical) AGAINST(?) OR '.
                    't.practical LIKE ? OR '.
                    'MATCH(t.literal) AGAINST(?) OR '.
                    'MATCH(t.meaning) AGAINST(?)'.
                ')',
                [$term, '%'.$term.'%', $term, '%'.$term.'%', $term, $term]
            );

        // Limit scope to a specific language.
        if (isset($options['lang']) && $lang = Language::findByCode($options['lang'])) {
            $builder->join('definition_language AS pivot', 'pivot.definition_id', '=', 'd.id')
                ->where('pivot.language_id', '=', DB::raw($lang->id));
        }

        // Limit scope to a specific definition type.
        if (isset($options['type']) && in_array($options['type'], static::types())) {
            $builder->where('d.type', '=', DB::raw(static::getTypeConstant($options['type'])));
        }

        // Limit scope to certain tags
        if (isset($options['tags']) && count($options['tags'])) {
            $tagIds = Tag::whereIn('title', $options['tags'])->lists('id');

            $builder->join('definition_tag AS tag_pivot', 'tag_pivot.definition_id', '=', 'd.id')
                ->whereIn('tag_pivot.tag_id', $tagIds);
        }

        return $builder;
    }

    /**
     * Scores a definition model between 0 and 1.
     *
     * @param object $rawScore
     * @return float
     */
    protected static function getSearchScore($rawScore)
    {
        return
            $rawScore->title_score * 10 +
            $rawScore->title_score_low * 1.5 +
            // $rawScore->transliteration_score +
            $rawScore->practical_score * ($rawScore->practical_score_multiplier + 0.8) +
            $rawScore->practical_score_low * 0.5 +
            $rawScore->literal_score * 0.8 +
            $rawScore->meaning_score * 0.8;
    }

    /**
     * Retrieves definitions and their relations in the context of a search.
     *
     * @param array $IDs
     * @return \Illuminate\Support\Collection
     */
    protected static function getSearchResults(array $IDs)
    {
        return self::with('languages', 'titles.alphabet', 'translations')
                        ->whereIn('id', $IDs)->get();
    }

    /**
     * Normalizes the search score and formats a model for search results.
     *
     * @param object $definition
     * @param object $scores
     * @param float $maxScore
     */
    protected static function normalizeSearchResult($definition, $scores, $maxScore)
    {
        // If a title is an exact match, assign max score.
        if ($scores->title_score > 0) {
            $definition->score = 1;
        }

        // If a translation is an exact match, assign second-highest score.
        elseif ($scores->practical_score_multiplier > 0) {
            $definition->score = 0.95;
        }

        // In any other case, assign a score out of 0.9.
        else {
            $definition->score = $scores->total * 0.9 / $maxScore;
        }

        // Embeds.
        $definition->setAttribute('mainTitle', $definition->mainTitle);
        $definition->setAttribute('translationData', $definition->translationData);
        $definition->setAttribute('mainLanguage', $definition->mainLanguage);
    }


    //
    //
    // Translations-related methods.
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Retrieves a translation relation.
     *
     * @param string $lang  A language code.
     * @return App\Models\Translation|false
     */
    public function getTranslationRelation($lang)
    {
        $found = Arr::where($this->translations, function ($key, $translation) use ($lang) {
            return $translation->language == $lang;
        });

        return count($found) == 1 ? $found[0] : false;
    }

    /**
     * Checks whether the attribute of a translation is empty or not.
     *
     * @param string $lang      A language code.
     * @param string $attribute The attribute to check.
     * @return bool
     */
    protected function _hasTranslationAttribute($lang, $attribute)
    {
        if ($translation = $this->getTranslationRelation($lang)) {
            return strlen($translation->$attribute);
        }

        return false;
    }

    /**
     * Retrieves a translation attribute.
     *
     * @param string $lang      A language code.
     * @param string $attribute The attribute to retrieve.
     * @param string $default   The default value to return.
     * @return string
     */
    protected function _getTranslationAttribute($lang, $attribute, $default = null)
    {
        if ($translation = $this->getTranslationRelation($lang)) {
            return strlen($translation->$attribute) ? $translation->$attribute : $default;
        }

        return $default;
    }

    /**
     * Sets a translation attribute.
     *
     * @param string $lang      The language code for the translation, e.g. 'en'.
     * @param string $attribute The attribute to update, e.g. 'translation' or 'meaning'.
     * @param string $data      The data to store for the attribute.
     * @param bool $create      Whether to create a new translation relation if one doesn't already exist.
     */
    protected function _setTranslationAttribute($lang, $attribute, $data, $create = false)
    {
        // Update an existing translation.
        if ($translation = $this->getTranslationRelation($lang)) {
            $translation->$attribute = $data;
            $translation->save();
        }

        // Create a new translation.
        elseif ($create === true && $attribute == 'practical') {
            $this->translations()->create([
                'language' => $lang,
                'practical' => $data,
            ]);
        }
    }

    //
    // Translations attribute.
    //

    public function hasPracticalTranslation($lang)
    {
        return $this->_hasTranslationAttribute($lang, 'practical');
    }

    public function getPracticalTranslation($lang = 'eng')
    {
        return $this->_getTranslationAttribute($lang, 'practical');
    }

    public function setPracticalTranslation($lang, $translation, $create = false)
    {
        return $this->_setTranslationAttribute($lang, 'practical', $translation, $create);
    }

    //
    // Literal translations attribute.
    //

    public function hasLiteralTranslation($lang)
    {
        return $this->_hasTranslationAttribute($lang, 'literal');
    }

    public function getLiteralTranslation($lang = 'en')
    {
        return $this->_getTranslationAttribute($lang, 'literal');
    }

    public function setLiteralTranslation($lang, $translation)
    {
        return $this->_setTranslationAttribute($lang, 'literal', $translation);
    }

    //
    // Meanings attribute.
    //

    public function hasMeaning($lang)
    {
        return $this->_hasTranslationAttribute($lang, 'meaning');
    }

    public function getMeaning($lang = 'en')
    {
        return $this->_getTranslationAttribute($lang, 'meaning');
    }

    public function setMeaning($lang, $meaning)
    {
        return $this->_setTranslationAttribute($lang, 'meaning', $meaning);
    }


    //
    //
    // Accessors and mutators.
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Accessor for $this->mainLanguage.
     *
     * @param $str
     * @return \App\Models\Language
     */
    public function getMainLanguageAttribute()
    {
        $mainLanguage = null;

        // Loop through related languages.
        if ($code = $this->getParam('mainLang', false)) {
            foreach ($this->languages as $lang) {
                if ($lang->code == $code) {
                    $mainLanguage = $lang;
                    break;
                }
            }
        }

        // Or pick first language as a default.
        if (! $mainLanguage) {
            $mainLanguage = $this->languages->first();
        }

        return $mainLanguage;
    }

    /**
     * Accessor for $this->type.
     *
     * @param int $type
     * @return string
     */
    public function getTypeAttribute($type = 0)
    {
        return Arr::get($this->types, $type, $this->types[static::TYPE_WORD]);
    }

    /**
     * Mutator for $this->type.
     *
     * @todo    Review logic...
     *
     * @param string|int $type
     * @return void
     */
    public function setTypeAttribute($type)
    {
        // Try to find the right type constant.
        if (in_array(strtolower($type), $this->types)) {
            $type = array_keys($this->types, strtolower($type))[0];
        }

        $this->attributes['type'] = $type;
    }

    /**
     * Accessor for $this->rawType.
     */
    public function getRawTypeAttribute($type = '')
    {
        return $this->getAttributeFromArray('type');
    }

    /**
     * Accessor for $this->subType.
     */
    public function getSubTypeAttribute($subType = '')
    {
        // "subType" will be null, since it will try to retrieve it from $this->attributes['subType']
        // which doesn't exist. We set it to the correct value here.
        if (! $subType = $this->rawSubType) {
            // Set and return a default type/sub-type.
            if (! $this->rawType) {
                $this->attributes['type'] = static::TYPE_WORD;
            }

            $this->attributes['sub_type'] = $subType = $this->defaultSubTypes[$this->rawType];
        }

        foreach ($this->types as $index => $type) {
            if (array_has($this->subTypes[$index], $subType)) {
                return array_get($this->subTypes[$index], $subType);
            }
        }

        return $subType;
    }

    /**
     * Mutator for $this->subType.
     *
     * @param string $subType
     * @return void
     */
    public function setSubTypeAttribute($subType)
    {
        // Try to find the right key for this sub-type.
        $typeKey = $this->attributes['type'];
        if (in_array(strtolower($subType), $this->subTypes[$typeKey])) {
            $subType = array_keys($this->subTypes[$typeKey], strtolower($subType))[0];
        }

        $this->attributes['sub_type'] = $subType;
    }

    /**
     * Accessor for $this->rawSubType.
     */
    public function getRawSubTypeAttribute($subType = '')
    {
        return $this->getAttributeFromArray('sub_type');
    }

    public function getTitlesArrayAttribute($titles)
    {
        return $this->titles;
    }

    /**
     * Accessor for $this->mainTitle.
     *
     * @return string
     */
    public function getMainTitleAttribute($title = '')
    {
        return $this->titles[0]->title;
    }

    /**
     * Accessor for $this->title (alias of $this->mainTitle).
     */
    public function getTitleAttribute($title = '')
    {
        return $this->mainTitle;
    }

    /**
     * Accessor for $this->titleString.
     *
     * @return string
     */
    public function getTitleStringAttribute($str = '')
    {
        return $this->titles->implode('title', ', ');
    }

    /**
     * Accessor for $this->titleList.
     *
     * @return array
     */
    public function getTitleListAttribute($list = [])
    {
        return $this->titles;
    }

    /**
     * Accessor for $this->altTitles.
     *
     * @deprecated  2016-05-15
     *
     * @return string
     */
    public function getAltTitlesAttribute($altTitles = '')
    {
        $str = '';

        if (count($this->titles) > 1) {
            $str = $this->titles->slice(1)->implode('title', ', ');
        }

        return $str;
    }

    /**
     * Accessor for $this->relatedDefinitionList.
     */
    public function getRelatedDefinitionListAttribute()
    {
        if (! count($this->relatedDefinitions)) {
            return new Collection;
        }

        return self::whereIn('id', $this->relatedDefinitions)->get();
    }

    /**
     * Accessor for $this->state.
     *
     * @param int $state
     * @return string
     */
    public function getStateAttribute($state = 0)
    {
        return array_get($this->states, $state, $this->states[static::STATE_VISIBLE]);
    }

    /**
     * Mutator for $this->state.
     *
     * @param string $state
     * @return void
     */
    public function setStateAttribute($state)
    {
        // If the state is already numeric, assume we're using a constant.
        if (is_numeric($state) && array_key_exists($state, $this->states)) {
            $this->attributes['state'] = (int) $state;
        }

        // Else, try to find the right constant.
        else {
            $this->attributes['state'] = is_int(array_search(strtolower($state), $this->states)) ?
                array_flip($this->states)[$state] : self::STATE_VISIBLE;
        }
    }

    /**
     * Accessor for $this->rawState.
     */
    public function getRawStateAttribute($state = 0)
    {
        return $this->getAttributeFromArray('state');
    }

    /**
     * Accessor for $this->languageList.
     *
     * @return array
     */
    public function getLanguageListAttribute($list = [])
    {
        $codes = [];

        foreach ($this->languages as $lang) {
            $codes[$lang->code] = $lang->name;
        }

        return $codes;
    }

    /**
     * Accessor for $this->tagList.
     *
     * @return array
     */
    public function getTagListAttribute($tags = [])
    {
        $list = [];

        foreach ($this->tags as $tag) {
            $list[] = $tag->title;
        }

        return $list;
    }

    /**
     * Accessor for $this->language.
     *
     * @return array
     */
    public function getLanguageAttribute()
    {
        return $this->getLanguageListAttribute();
    }

    /**
     * Accessor for $this->practicalTranslations.
     */
    public function getPracticalTranslationsAttribute()
    {
        $translations = [];

        foreach ($this->translations as $translation) {
            $translations[$translation->language] = $translation->practical;
        }

        return $translations;
    }

    /**
     * Accessor for $this->literalTranslations.
     */
    public function getLiteralTranslationsAttribute()
    {
        $literals = [];

        foreach ($this->translations as $translation) {
            $literals[$translation->language] = $translation->literal;
        }

        return $literals;
    }

    /**
     * Accessor for $this->meanings.
     */
    public function getMeaningsAttribute()
    {
        $meanings = [];

        foreach ($this->translations as $translation) {
            $meanings[$translation->language] = $translation->meaning;
        }

        return $meanings;
    }

    /**
     * Accessor for $this->translation.
     */
    public function getTranslationAttribute()
    {
        $results = $this->translations->where('language', 'eng');

        return $results->count() ? $results->first() : null;
    }

    /**
     * Accessor for $this->translationList. Used to combine translation data when arraying this model.
     *
     * @deprecated
     */
    public function getTranslationListAttribute()
    {
        return [
            'practical' => $this->practicalTranslations,
            'literal'   => $this->literalTranslations,
            'meaning'   => $this->meanings,
        ];
    }

    /**
     * Accessor for $this->translationData. Used to combine translation data when arraying this
     * model.
     */
    public function getTranslationDataAttribute()
    {
        $data = [];

        if (count($this->translations)) {
            foreach ($this->translations as $translation) {
                $data[$translation->language] = array_only($translation->toArray(), [
                    'practical',
                    'literal',
                    'meaning',
                ]);
            }
        }

        return $data;
    }

    /**
     * Accessor for $this->references.
     */
    public function getReferencesAttribute()
    {
        return $this->translation ? $this->translation->references : new Collection;
    }

    /**
     * Accessor for $this->referenceList.
     */
    public function getReferenceListAttribute()
    {
        return $this->references;
    }

    /**
     * Accessor for $this->resourceType.
     *
     * @return string
     */
    public function getResourceTypeAttribute()
    {
        return 'definition';
    }


    //
    //
    // Import/export-related methods.
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    public function setRelationToBeImported($relation, $data)
    {
        $this->relationsToBeImported[$relation] = $data;
    }

    public function getRelationsToBeImported()
    {
        return $this->relationsToBeImported;
    }

    /**
     * Checks Definition properties before saving to database.
     *
     * @param \App\Models\Definition $def
     * @return bool
     */
    public static function checkAttributes($def)
    {
        // Check relations to be imported, if any.
        $relations = $def->getRelationsToBeImported();
        if (count($relations)) {
            // This definition must exist in some language.
            if (! isset($relations['languages']) || ! count($relations['languages'])) {
                return false;
            }

            // Check that languages exist in our database.
            foreach ($relations['languages'] as $code) {
                if (! Language::where('code', $code)->exists()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Tries to import relations when importing a definition.
     *
     * @param \App\Models\Definition $def
     * @return bool
     */
    public static function importRelations($def)
    {
        $relations = $def->getRelationsToBeImported();
        if (! count($relations)) {
            return true;
        }

        // Update language relations.
        foreach ($relations['languages'] as $code) {
            if (! Language::addRelatedDefinition($code, $def)) {
                return false;
            }
        }

        // Update translations relations.
        if (isset($relations['translations']) && count($relations['translations'])) {
            foreach ($relations['translations'] as $lang => $translation) {
                $def->translations()->save(new Translation([
                    'language' => $lang,
                    'translation' => $translation,
                    'literal' => Arr::get($relations['literals'], $lang, ''),
                    'meaning' => Arr::get($relations['meanings'], $lang, ''),
                ]));
            }
        }

        return true;
    }

    public function updateRelations(array $relations)
    {
        // Performance check.
        if (empty($relations)) {
            return;
        }

        foreach ($relations as $relation => $data) {
            $methodName = 'update'.ucfirst(strtolower($relation)).'Relation';
            if (method_exists($this, $methodName)) {
                $this->$methodName($data);
            }
        }
    }

    /**
     * Updates the language relations.
     *
     * @param array $languages
     */
    public function updateLanguageRelation(array $languages)
    {
        // Perormance check.
        if (empty($languages)) {
            return;
        }

        // Make sure we have an array of IDs.
        foreach ($languages as $index => $langID) {
            if (! is_numeric($langID)) {
                // If we can't find the language by code, assume it was invalid.
                if (! $lang = Language::findByCode($langID)) {
                    unset($languages[$index]);
                    continue;
                }

                // Replace the code with an ID.
                $languages[$index] = $lang->id;
            }
        }

        // Sync language relations.
        $this->languages()->sync($languages);

        // Make the first language the main language.
        if ($lang = Language::find($languages[0])) {
            $this->setParam('mainLang', $lang->code);
            $this->save();
        }
    }

    /**
     * Updates or creates one or more practical translations.
     *
     * @param array $translations
     */
    public function updatePracticalRelation(array $translations)
    {
        foreach ($translations as $code => $translation) {
            $this->setPracticalTranslation($code, $translation, true);
        }
    }

    /**
     * Updates one or more literal translations in the translation relations.
     *
     * @param array $translations
     */
    public function updateLiteralRelation(array $translations)
    {
        foreach ($translations as $code => $translation) {
            $this->setLiteralTranslation($code, $translation);
        }
    }

    /**
     * Updates one or more meanings in the translation relations.
     *
     * @param array $translations
     */
    public function updateMeaningRelation(array $meanings)
    {
        foreach ($meanings as $code => $meaning) {
            $this->setMeaning($code, $meaning);
        }
    }
}
