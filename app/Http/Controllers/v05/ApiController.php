<?php

namespace App\Http\Controllers\v05;

use App\Models\Language;
use App\Models\Definition;
use App\Utilities\Locales;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * @return string
     */
    public function version()
    {
        return 'Dora Boateng API 0.5';
    }

    /**
     * @todo   Deprecate search term in URI.
     * @param  string $query
     * @return array
     */
    public function generalSearch($query = null)
    {
        $query = $this->request->get('q', $query);

        // Retrieve search parameters (omit "offset" and "limit").
        $options = ['lang' => $this->request->get('language', '')];

        // Lookup cultures.
        $results = new Collection;

        // Add definitions.
        $results = $results->merge(Definition::search($query, $options));

        // Add languages.
        $results = $results->merge(Language::search($query, $options));

        // Sort results by score.
        $results = $results->sortByDesc(function ($result) {
            return $result->score;
        })->values();

        // Apply "offset" and "limit" search parameters.
        $limit = max([
            Definition::SEARCH_LIMIT,
            Language::SEARCH_LIMIT,
        ]);

        return [
            'results' => $results->slice(
                $this->request->input('offset', 0),
                $this->request->input('limit', $limit)
            )
        ];
    }

    /**
     * Returns the latest resource IDs and types
     *
     * @return Collection
     */
    public function latest()
    {
        $results = new Collection;

        // Latest definitions
        $results = $results->merge(
            Definition::select('id')->orderBy('created_at')->take(100)->get()
        );

        // Latest languages
        $results = $results->merge(
            Language::select('id', 'code')->orderBy('created_at')->take(20)->get()
        );

        return $results;
    }

    /**
     * List of supported locales
     *
     * @return array
     */
    public function locales()
    {
        switch (strtolower(trim($this->request->get('format')))) {
            case 'iso-639-3':
                return Locales::SUPPORTED_LANGUAGES;

            case 'combined':
                return Locales::all();

            default:
                return Locales::SUPPORTED_LOCALES;
        }
    }
}
