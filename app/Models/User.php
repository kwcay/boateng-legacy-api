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
use App\Traits\ObfuscatableTrait as Obfuscatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\CamelCaseAttributesTrait as CamelCaseAttrs;

class User extends Authenticatable
{
    // use Authenticatable, CamelCaseAttrs, CanResetPassword, Embedable, Exportable, HasRoles, ObfuscatesID, SoftDeletes;
    use CamelCaseAttrs, Embedable, Exportable, HasApiTokens, Notifiable, Obfuscatable, SoftDeletes;


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
        'urn' => null,
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
        'uri',
        'params',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Attributes that SHOULD be appended to the model's array form.
     */
    protected $appends = [
        'uniqueId',
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

    /**
     * Accessor for $this->urn.
     *
     * @param  string  $urn
     * @return string
     */
    public function getUrnAttribute($urn = '')
    {
        return str_replace('local/', 'doraboateng:', $urn ?: $this->uri);
    }
}
