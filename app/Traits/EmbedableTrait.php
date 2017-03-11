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
     * @param   Illuminate\Database\Eloquent\Builder        $query
     * @param   string|array|Illuminate\Support\Collection  $embed
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmbed($query, $embed)
    {
        // Relations and accessors to append.
        $separator = isset($this->embedSeparator) ? $this->embedSeparator : ',';
        $attributes = is_string($embed) ? @explode($separator, $embed) : (array) $embed;

        // Extract the accessors from the list of attributes.
        $embedable = isset($this->embedable) ? $this->embedable : [];
        $accessors = array_intersect(array_keys($embedable), $attributes);

        // Extract the relations from the list of attributes.
        $relations = array_filter(array_diff($attributes, array_keys($embedable)));

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

        // TODO: Temporarily set "appends" array to embedable accessors.

        return $query->with($relations);
    }

    /**
     * Applies the given accessors to the model.
     *
     * @todo    Find a way to include this in "scopeEmbed"
     *
     * @param   string|array|Illuminate\Support\Collection  $embed
     * @return  void
     */
    public function applyEmbedableAttributes($embed)
    {
        // Relations and accessors to append.
        $separator = isset($this->embedSeparator) ? $this->embedSeparator : ',';
        $attributes = is_string($embed) ? @explode($separator, $embed) : (array) $embed;

        // Extract the accessors from the list of attributes.
        $embedable = isset($this->embedable) ? $this->embedable : [];
        $accessors = array_intersect(array_keys($embedable), $attributes);

        // Append extra attributes.
        if (count($accessors))
        {
            foreach ($accessors as $accessor)
            {
                $this->setAttribute($accessor, $this->getAttributeValue($accessor));
            }
        }
    }
}
