<?php

namespace App\Models\Definitions;

use App\Models\Language;
use App\Models\Definition;

class Word extends Definition
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set the definiiton type.
        $this->attributes['type'] = static::TYPE_WORD;
    }

    /**
     * Retrieves a random word.
     *
     * @param App\Models\Language|string $lang
     * @param array $relations
     * @return mixed
     */
    public static function random($lang = null, array $relations = ['languages', 'translations'])
    {
        if (is_string($lang)) {
            $lang = Language::findByCode($lang);
        }

        // Get query builder.
        $query = $lang instanceof Language ? $lang->definitions() : static::query();

        // Return a random definition.
        return $query
            ->where('type', static::TYPE_WORD)
            ->with($relations)
            ->orderByRaw('RAND()')
            ->first();
    }

    /**
     * Retrieves word of the day.
     *
     * @param string $lang
     * @param string $relations
     * @return App\Models\Definition|null
     */
    public static function daily($lang = '*', $embed = '')
    {
        return static::dailyByType(static::TYPE_WORD, $lang, $embed);
    }

    /**
     * Searches the database for words.
     *
     * @param string $query     Search query.
     * @param array $options    Search options.
     * @return array
     */
    public static function search($query, array $options = [])
    {
        return parent::search($query, array_merge($options, [
            'type' => static::types()[static::TYPE_WORD],
        ]));
    }

    /**
     * Gets the list of sub types for this definition.
     */
    public function getSubTypes()
    {
        return $this->subTypes[Definition::TYPE_WORD];
    }
}
