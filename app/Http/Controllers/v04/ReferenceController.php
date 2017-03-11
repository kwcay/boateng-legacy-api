<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 *
 * @version 0.4
 * @brief   Handles reference-related API requests.
 */
namespace App\Http\Controllers\v0_4;

use Request;
use App\Http\Requests;
use App\Models\Reference;
use App\Http\Controllers\Controller;
use App\Http\Controllers\v0_4\Contract as BaseController;

class ReferenceController extends BaseController
{
    /**
     * Returns a tag resource.
     *
     * @param string $id    Unique ID of tag.
     * @return object
     */
    public function show($id)
    {
        // Performance check.
        if (! $id = Reference::decodeId($id)) {
            return response('Invalid Reference ID.', 400);
        }

        // List of relations and attributes to append to results.
        $embed = $this->getEmbedArray(
            Request::get('embed'),
            Reference::$appendable
        );

        // Retrieve definition object
        if (! $reference = Reference::with($embed['relations']->toArray())->find($id)) {
            return response('Reference Not Found.', 404);
        }

        return $reference;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        return response('Not Implemented.', 501);

        // Retrieve data for new definition.
        $data = Request::only(['title', 'alt_titles', 'sub_type']);

        // Create the record in the database.
        return $this->save($definition, $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @throws \Exception
     * @return Response
     */
    public function update($id)
    {
        return response('Not Implemented.', 501);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @throws \Exception
     * @return Response
     */
    public function destroy($id)
    {
        return response('Not Implemented.', 501);
    }
}
