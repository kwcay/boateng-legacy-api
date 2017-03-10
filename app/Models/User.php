<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 * TO REVIEW
 */
namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Traits\EmbedableTrait as Embedable;
use App\Traits\ExportableTrait as Exportable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ObfuscatableTrait as ObfuscatesID;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\CamelCaseAttributesTrait as CamelCaseAttrs;

class User extends Authenticatable
{
    // use Authenticatable, CamelCaseAttrs, CanResetPassword, Embedable, Exportable, HasRoles, ObfuscatesID, SoftDeletes;
    use CamelCaseAttrs, Embedable, Exportable, HasApiTokens, Notifiable, ObfuscatesID, SoftDeletes;


    //
    //
    // Attributes for Frnkly\Traits\Embedable
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
    // Attributes used by App\Traits\ExportableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * The attributes that should be hidden from the model's array form when exporting data to file.
     */
    protected $hiddenFromExport = [
        'id',
    ];

    //
    //
    // Attributes for App\Traits\ObfuscatableTrait
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @var int
     */
    public $obfuscatorId = 89;


    //
    //
    // Main attributes
    //
    ////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uri',
        'name',
        'email',
        'password',
        'createdAt',
        'deletedAt',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];


    //
    //
    // Helper methods
    //
    ////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Looks up a user by their email address.
     *
     * @param   string|\App\Models\User $email
     * @return  \App\Models\User|null
     */
    public static function findByEmail($email)
    {
        // Performance check.
        if ($email instanceof static) {
            return $email;
        }

        // Retrieve user by email.
        return static::where('email', '=', $email)->first();
    }

    //
    //
    // Accessors and mutators.
    //
    ////////////////////////////////////////////////////////////////////////////////////////////

    // ...
}
