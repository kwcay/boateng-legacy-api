<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 * TO REVIEW
 */
namespace App\Models;

use App\Traits\ValidatableTrait as Validatable;
use App\Traits\ExportableTrait as Exportable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transliteration extends Model
{
    use Validatable, Exportable, SoftDeletes;


    //
    //
    // Main attributes
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @var array   Attributes which aren't mass-assignable.
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var array   Attributes that should be mutated to dates.
     */
    protected $dates = ['deleted_at'];

    /**
     * @var array
     */
    protected $casts = [];

    /**
     * @var array   Validation rules.
     */
    public $validationRules = [];


    //
    //
    // Relations
    //
    ////////////////////////////////////////////////////////////////////////////////////////////



    //
    //
    // Main methods
    //
    ////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
}
