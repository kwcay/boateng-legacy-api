<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
namespace App\Traits;

trait EmbedableTrait
{
    /**
     * Includes the required relations in the query builder.
     *
     * @todo    Find a way to apply accessors through this method.
     *
     * @param   Illuminate\Database\Eloquent\Builder $query
     * @param   string|array $embed
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmbed($query, $embed)
    {
        return $query->with($this->parseEmbeds($embed)['relations']);
    }

    /**
     * Applies the given accessors to the model.
     *
     * @todo    Find a way to include this in "scopeEmbed"
     *
     * @param   string|array  $embed
     * @return  Illuminate\Database\Eloquent\Model
     */
    public function applyEmbedableAttributes($embed)
    {
        $accessors = $this->parseEmbeds($embed)['accessors'];

        // Append extra attributes.
        if (count($accessors))
        {
            foreach ($accessors as $accessor)
            {
                $this->setAttribute($accessor, $this->getAttributeValue($accessor));
            }
        }

        return $this;
    }

    /**
     * Parses an embed string and determines which attributes are
     * relations and which ones are accessors.
     *
     * @param array|string $embed
     * @return array
     */
    protected function parseEmbeds($embed)
    {
        // Relations and accessors to append.
        $separator  = isset($this->embedSeparator) ? $this->embedSeparator : ',';
        $attributes = is_string($embed) ? @explode($separator, $embed) : (array) $embed;

        // Extract the accessors from the list of attributes.
        $embedable  = isset($this->embedable) ? $this->embedable : [];
        $accessors  = array_intersect(array_keys($embedable), $attributes);

        // Extract the relations from the list of attributes.
        $relations  = array_filter(array_diff($attributes, array_keys($embedable)));

        // Remove invalid relations.
        // TODO

        // If the accessors require any relation, add them to the list.
        foreach ($accessors as $accessor)
        {
            // Performance check.
            if (empty($embedable[$accessor])) {
                continue;
            }

            $relations = array_merge($relations, $embedable[$accessor]);
        }

        // Remove duplicates.
        $relations = array_unique($relations);

        // Return parsed result.
        return [
            'accessors' => $accessors,
            'relations' => $relations,
        ];
    }
}
