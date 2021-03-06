<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Http\Controllers\v0_4;

use App\Models\Language;
use App\Models\Definition;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the version number of the API.
     */
    public function version()
    {
        return 'Dora Boateng API 0.4';
    }

    /**
     * @param string $query
     */
    public function generalSearch($query)
    {
        // Retrieve search parameters (omit "offset" and "limit").
        $options = ['lang' => $this->request->input('lang', '')];

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
}
