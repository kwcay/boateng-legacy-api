<?php
/**
 * Copyright Dora Boateng(TM) 2015, all rights reserved.
 */
namespace App\Traits;

use App;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;

trait ObfuscatableTrait
{
    /**
     * @var
     */
    protected static $obfuscator;

    /**
     * @var string
     */
    protected $obfuscatedId;

    /**
     * @return mixed
     */
    public static function getObfuscator()
    {
        if (! isset(static::$obfuscator)) {
            static::$obfuscator = App::make('Obfuscator');
        }

        return static::$obfuscator;
    }

    /**
     * @return int|string   Obfuscated ID, or 0.
     */
    public function getUniqueId()
    {
        if (is_null($this->obfuscatedId)) {
            $this->obfuscatedId = $this->id > 0 ? static::getObfuscator()->encode($this->id) : 0;
        }

        return $this->obfuscatedId;
    }

    /**
     * Accessor for $this->uniqueId.
     */
    public function getUniqueIdAttribute()
    {
        return $this->getUniqueId();
    }

    /**
     * Encodes an ID
     *
     * @param int $id
     * @return string|null
     */
    public static function encodeId($id)
    {
        return static::getObfuscator()->encode($id);
    }

    /**
     * Decodes an ID.
     *
     * @param int|string $encodedId
     * @return int
     */
    public static function decodeId($encodedId)
    {
        if ($id = static::getObfuscator()->decode($encodedId)) {
            return $id;
        }

        return 0;
    }

    /**
     * Find a model by its primary key.
     *
     * @param int|string $id
     * @param array $columns
     * @return \Illuminate\Support\Collection|static|null
     */
    public static function find($id, $columns = ['*'])
    {
        if ($id = static::decodeId($id)) {
            return static::query()->find($id, $columns);
        }
    }

    /**
     * Find a soft-deleted model by its primary key.
     *
     * @param int|string $id
     * @param array $columns
     * @return \Illuminate\Support\Collection|static|null
     */
    public static function findTrashed($id, $columns = ['*'])
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive(get_called_class()))) {
            throw new Exception(get_called_class().' does not soft-delete.');
        }

        if ($id = static::decodeId($id)) {
            return static::onlyTrashed()->find($id, $columns);
        }
    }

    /**
     * @param int|string $id
     * @param array $columns
     * @return mixed
     */
    public static function findOrNew($id, $columns = ['*'])
    {
        // Un-obfuscate ID
        if (is_string($id) && ! is_numeric($id) && strlen($id) >= 8) {
            $id = static::getObfuscator()->decode($id)[0];
        }

        return parent::findOrNew($id, $columns);
    }

    /**
     * @param int|string $id
     * @param array $columns
     * @return mixed
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        // Un-obfuscate ID
        if (is_string($id) && ! is_numeric($id) && strlen($id) >= 8) {
            $id = static::getObfuscator()->decode($id)[0];
        }

        return parent::findOrFail($id, $columns);
    }

    /**
     * Destroy the models for the given IDs.
     *
     * @param  array|int  $ids
     * @return int
     */
    public static function destroy($ids)
    {
        // Decode IDs
        $ids = is_array($ids) ? $ids : func_get_args();

        foreach ($ids as &$id) {
            if ($newId = self::decodeId($id)) {
                $id = $newId;
            }
        }

        return parent::destroy($ids);
    }
}
