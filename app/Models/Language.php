<?php
/**
 * Copyright Dora Boateng(TM) 2015, all rights reserved.
 */
namespace App\Models;

use DB;
use Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\EmbedableTrait as Embedable;
use App\Traits\ExportableTrait as Exportable;
use App\Traits\SearchableTrait as Searchable;
use App\Traits\ValidatableTrait as Validatable;
use App\Traits\ObfuscatableTrait as Obfuscatable;
use App\Traits\CamelCaseAttributesTrait as CamelCaseAttrs;

class Language extends Model
{
    use CamelCaseAttrs, Embedable, Exportable, Obfuscatable, Searchable, SoftDeletes, Validatable;


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
        'parentName'        => ['parent'],
        'definitionCount'   => ['definitions'],
        'firstDefinition'   => ['definitions'],
        'latestDefinition'  => ['definitions'],
        'randomDefinition'  => ['definitions'],
    ];


    //
    //
    // Attributes used by App\Traits\ExportableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Attributes that should be hidden when exporting data to file.
     *
     * @var array
     */
    protected $hiddenOnExport = [
        'id',
        'updated_at',
        'definitions',
        'pivot',
        'uniqueId',
        'resourceType',
    ];

    /**
     * Attributes that should be appended when exporting data to file.
     *
     * @var array
     */
    protected $appendsOnExport = [];


    //
    //
    // Attributes used by App\Traits\SearchableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    const SEARCH_LIMIT = 100;       // Maximum number of results to return on a search.
    const SEARCH_QUERY_LENGTH = 2;  // Minimum length of search query.

    /**
     * Indicates whether search results can be filtered by tags.
     *
     * @var bool
     */
    public static $searchIsTaggable = false;


    //
    //
    // Main attributes
    //
    ////////////////////////////////////////////////////////////////////////////////////////////



    /**
     * Attributes which aren't mass-assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden from the model's array form.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'definitions',
        'pivot',
        'parent',
        'deleted_at',
    ];

    /**
     * Attributes that SHOULD be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'uniqueId',
        'resourceType',
    ];

    /**
     * Attributes that CAN be appended to the model's array form.
     *
     * @var array
     */
    public static $appendable = [
        'parentName',
        'definitionCount',
        'firstDefinition',
        'latestDefinition',
        'randomDefinition',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'code' => 'string',
        'parent_code' => 'string',
        'name' => 'string',
        'alt_names' => 'string',
    ];

    /**
     * Attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'parentCode',
        'name',
        'transliteration',
        'altNames',
        'createdAt',
        'deletedAt',
    ];

    /**
     * Validation rules.
     */
    public $validationRules = [
        'code' => 'sometimes|required|min:3|max:7|unique:languages',
        'parent_code' => 'min:3|max:7',
        'name' => 'required|min:2',
        'alt_names' => 'min:2',
    ];


    //
    //
    // Relations
    //
    ////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Defines relation to Language model (parent relation).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('App\Models\Language', 'parent_code', 'code');
    }

    /**
     * Defines relation to Language model (children relation).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany('App\Models\Language', 'parent_code', 'code');
    }

    /**
     * Defines relation to Definition model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function definitions()
    {
        return $this->belongsToMany('App\Models\Definition');
    }

    /**
     * Defines relation to Alphabet model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function alphabets()
    {
        return $this->belongsToMany('App\Models\Alphabet', 'alphabet_language', 'language_id', 'alphabet_id');
    }

    /**
     * Defines relation to Country model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function countries()
    {
        return $this->belongsToMany('App\Models\Country', 'country_language', 'language_id', 'country_id');
    }


    //
    //
    // Helper methods
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Looks up a language model by code.
     *
     * @param string|\App\Models\Language $code
     * @param array $embed
     * @return \App\Models\Language|null
     */
    public static function findByCode($code, array $embed = [])
    {
        // Performance check.
        if ($code instanceof static) {
            return $code;
        }

        // Retrieve langauge by code.
        $code = static::sanitizeCode($code);

        return $code ? static::with($embed)->where(['code' => $code])->first() : null;
    }

    /**
     * Checks if a language with the given code exists.
     *
     * @param string $code
     * @return bool
     */
    public static function codeExists($code)
    {
        // Sanitize code.
        $code = static::sanitizeCode($code);

        return $code ? static::where('code', $code)->exists() : false;
    }

    /**
     *
     */
    public static function sortedBy($sort = 'name', $dir = 'asc')
    {
        return static::query()->orderBy($sort, $dir)->get();
    }

    /**
     * @param string $code
     * @return string|null
     */
    public static function sanitizeCode($code)
    {
        // Performance check.
        if (! is_string($code)) {
            return;
        }

        // A language code can contain letters and dashes.
        $code = preg_replace('/[^a-z\-]/', '', strtolower($code));

        // And will have the format "abc" or "abc-def"
        return preg_match('/^([a-z]{3}(-[a-z]{3})?)$/', $code) ? $code : null;
    }

    /**
     * @param string $code
     * @return bool
     */
    public static function isValidCode($code)
    {
        $sanitizedCode = self::sanitizeCode($code);

        if ($code != $sanitizedCode) {
            return false;
        }

        return (bool) preg_match('/^[a-z]{3}(-[a-z]{3})?$/i', $code);
    }


    //
    //
    // Accessors and mutators.
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Accessor for $this->parentName.
     *
     * @return string
     */
    public function getParentNameAttribute($data = null)
    {
        return $this->parent ? $this->parent->name : '';
    }

    /**
     * Accessor for $this->parentLanguage.
     *
     * @return string
     */
    public function getParentLanguageAttribute($data = null)
    {
        return $this->parent ?: null;
    }

    /**
     * Accessors for $this->countryString.
     */
    public function getCountryStringAttribute()
    {
        switch (count($this->countries)) {
            case 0:
                $countryStr = '';
                break;

            case 1:
                $countryStr = $this->countries[0]->name;
                break;

            default:
                $countryStr = '';
                for ($i = 0; $i < count($this->countries) - 2; $i++) {
                    $countryStr = $countryStr.$this->countries[$i]->name.', ';
                }

                $countryStr = $countryStr.$this->countries[$i++]->name.' and '.$this->countries[$i]->name;
        }

        return $countryStr;
    }

    /**
     * Accessor for $this->definitionCount.
     *
     * @return int
     */
    public function getDefinitionCountAttribute()
    {
        return $this->definitions()->count();
    }

    /**
     * Accessor for $this->firstDefinition.
     *
     * @return array
     */
    public function getFirstDefinitionAttribute()
    {
        $first = null;

        if ($definition = $this->definitions()->first()) {
            $first = [
                'mainTitle'         => $definition->titles[0]->title,
                'translationData'   => $definition->translationData,
                'type'              => $definition->type,
                'subType'           => $definition->subType,
                'uniqueId'          => $definition->uniqueId,
            ];
        }

        return $first;
    }

    /**
     * Accessor for $this->latestDefinition.
     *
     * @return array
     */
    public function getLatestDefinitionAttribute()
    {
        $latest = null;

        if ($definition = $this->definitions()->orderBy('created_at', 'DESC')->first()) {
            $latest = [
                'mainTitle'         => $definition->titles[0]->title,
                'translationData'   => $definition->translationData,
                'type'              => $definition->type,
                'subType'           => $definition->subType,
                'uniqueId'          => $definition->uniqueId,
            ];
        }

        return $latest;
    }

    /**
     * Accessor for $this->randomDefinition.
     *
     * @return array
     */
    public function getRandomDefinitionAttribute()
    {
        $random = null;

        if ($definition = $this->definitions()->orderByRaw('RAND()')->first()) {
            $random = [
                'mainTitle'         => $definition->titles[0]->title,
                'translationData'   => $definition->translationData,
                'type'              => $definition->type,
                'subType'           => $definition->subType,
                'uniqueId'          => $definition->uniqueId,
            ];
        }

        return $random;
    }

    /**
     * Accessor for $this->resourceType.
     *
     * @return string
     */
    public function getResourceTypeAttribute()
    {
        return 'language';
    }


    //
    //
    // Methods used by App\Traits\SearchableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @param string $term      Search query.
     * @param array $options    Search options.
     * @return Builder
     */
    protected static function getSearchQueryBuilder($term, array $options = [])
    {
        $builder = DB::table('languages AS l')

            // Create a temporary score column so we can sort the IDs.
            ->selectRaw(
                'l.id,'.
                'l.code = ? AS code_score, '.
                'l.parent_code = ? AS code_score_low, '.
                'l.name = ? AS name_score, '.
                'l.name LIKE ? AS name_score_low, '.
                'l.alt_names LIKE ? AS alt_score ',
                [$term, $term, $term, '%'.$term.'%', '%'.$term.'%']
            )

            // Try to search in a relevant way.
            ->whereRaw(
                '(l.code = ? OR '.
                'l.parent_code = ? OR '.
                'l.name = ? OR '.
                'l.name LIKE ? OR '.
                'l.alt_names LIKE ?)',
                [$term, $term, $term, '%'.$term.'%', '%'.$term.'%']
            );

        return $builder;
    }

    /**
     * Scores a language model between 0 and 1.
     *
     * @param object $rawScore
     * @return float
     */
    protected static function getSearchScore($rawScore)
    {
        return
            $rawScore->code_score * 3 +
            $rawScore->name_score * 3 +
            // $rawScore->transliteration_score * 2 +
            $rawScore->code_score_low * 1.5 +
            $rawScore->name_score_low +
            $rawScore->alt_score;
    }

    /**
     * @param array $IDs
     * @return \Illuminate\Support\Collection
     */
    protected static function getSearchResults(array $IDs)
    {
        return static::whereIn('id', $IDs)->get();
    }

    /**
     * Normalizes the search score and formats a model for search results.
     *
     * @param object $language
     * @param object $scores
     * @param float $maxScore
     */
    protected static function normalizeSearchResult($language, $scores, $maxScore)
    {
        // If the language name is an exact match, assign max score.
        if ($scores->name_score > 0) {
            $language->score = 1;
        }

        // If language code is an exact match, assign second-highest score.
        elseif ($scores->code_score > 0) {
            $language->score = 0.97;
        }

        // If language's parent code is an exact match, assign third-highest score.
        elseif ($scores->code_score_low > 0) {
            $language->score = 0.92;
        }

        // In any other case, assign a score out of 0.9.
        else {
            $language->score = $scores->total * 0.9 / $maxScore;
        }

        // Embeds.
        $language->setAttribute('parentName', $language->parentName);
    }

    // TODO: add description relations for each description (en, fr, ...)
    public function hasDescription($lang)
    {
        return false;
    }

    public function getDescription($lang = 'en')
    {
        return '';
    }

    public function setDescription($lang, $desc)
    {
    }

    //
    //
    // Import/export-related methods.
    //
    ////////////////////////////////////////////////////////////////////////////////////////////

     /**
      * Creates the relation between an language and a definition.
      */
     public static function addRelatedDefinition($code, $def)
     {
         // Keep a static array of languages so that we don't have to
         // retrieve them again and again from the database.
         static $languages;

         // Performance check.
         if (! strlen($code) || ! $def instanceof Definition) {
             Log::debug('Invalid code or definition object in Language::addRelatedDefinition.');

             return false;
         }

         // Retrieve language object.
         if (! isset($languages[$code])) {
             $languages[$code] = static::findByCode($code);

             if (! $languages[$code]) {
                 Log::debug('Language::addRelatedDefinition - Could not retrieve language object.');

                 return false;
             }
         }

         // Add relation.
         isset($def->id) && $def->id > 0
            ? $languages[$code]->definitions()->attach($def)
            : $languages[$code]->definitions()->save($def);

         return true;
     }

    /**
     * Retrieves country list (compiled with umpirsky/country-list library).
     *
     * @param string $locale    Language in which to retrieve country names
     * @return array            List of countries
     */
    public static function getCountryList($locale = 'en')
    {
        $locale = preg_replace('/[^a-z_]/', '', $locale);
        $list = file_exists(base_path().'/resources/countries/'.$locale.'.php') ?
            include base_path().'/resources/countries/'.$locale.'.php' :
            include base_path().'/resources/countries/en.php';

        return $list;
    }

    /**
     * Checks language properties before saving to database.
     *
     * @param \App\Models\Language $lang
     * @return bool
     */
    public static function checkAttributes($lang)
    {
        return true;
    }
}
