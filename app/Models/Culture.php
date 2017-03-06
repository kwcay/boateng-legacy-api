<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 * TO REVIEW
 */
namespace App\Models;

use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\EmbedableTrait as Embedable;
use App\Traits\ExportableTrait as Exportable;
use App\Traits\SearchableTrait as Searchable;
use App\Traits\ObfuscatableTrait as Obfuscatable;
use App\Traits\CamelCaseAttributesTrait as CamelCaseAttrs;

class Culture extends Model
{
    use CamelCaseAttrs, Embedable, Exportable, Obfuscatable, SoftDeletes;


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
    ];


    //
    //
    // Attributes for App\Traits\ExportableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Supported export formats.
     */
    public $exportFormats = ['yml', 'yaml', 'js', 'json'];

    /**
     * Attributes that should be hidden when exporting data to file.
     *
     * @var array
     */
    protected $hiddenOnExport = [
        'id',
        'uniqueId',
        'resourceType',
    ];

    /**
     * Attributes that should be appended when exporting data to file.
     *
     * @var array
     */
    protected $appendsOnExport = [
    ];


    //
    //
    // Attributes for App\Traits\ObfuscatableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @var int
     */
    public $obfuscatorId = 73;


    //
    //
    // Attributes for App\Traits\SearchableTrait
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
     * Associated MySQL table.
     */
    protected $table = 'cultures';

    /**
     * Attributes which aren't mass-assignable.
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden from the model's array form.
     */
    protected $hidden = [
        'id',
    ];

    /**
     * Attributes that SHOULD be appended to the model's array form.
     */
    protected $appends = [
        'uniqueId',
        'resourceType',
    ];

    /**
     * Attributes that CAN be appended to the model's array form.
     */
    public static $appendable = [
    ];

    /**
     * Attributes that should be mutated to dates.
     */
    protected $dates = ['deleted_at'];

    /**
     * Attributes that should be cast when assigned.
     */
    protected $casts = [
        'language_id' => 'integer',
        'name' => 'string',
        'alt_names' => 'string',
    ];

    /**
     * Validation rules.
     */
    public $validationRules = [
        'language_id' => 'exists:languages,id',
        'name' => 'required|string|min:1|max:400',
        'alt_names' => 'string|min:1|max:400',
    ];


    //
    //
    // Relations
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    // ...


    //
    //
    // Main methods
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }


    //
    //
    // Methods for App\Traits\SearchableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    // ...


    //
    //
    // Accessors and mutators.
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * Accessor for $this->languageId.
     *
     * @return string
     */
    public function getLanguageIdAttribute($id = 0)
    {
        return Language::encodeId($id);
    }

    /**
     * Accessor for $this->resourceType.
     *
     * @return string
     */
    public function getResourceTypeAttribute()
    {
        return 'culture';
    }
}
