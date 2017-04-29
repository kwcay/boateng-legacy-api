<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 *
 * @version 0.4
 * @brief   Handles user-related API requests.
 */
namespace App\Http\Controllers\v0_4;

use Request;
use App\Models\User;
use App\Http\Requests;
use App\Http\Controllers\v0_4\Controller;

class UserController extends Controller
{
    /**
     * TODO: restrict access to this endpoint
     */
    public function index()
    {
        return response('Not Implemented.', 501);
    }

    public function current()
    {
        if (! $user = Auth::user()) {
            return response('Unauthorized.', 401);
        }

        return $user;
    }

    public function show($id)
    {
        // Performance check.
        if (! $id = User::decodeId($id)) {
            return response(self::ERR_STR_INVALID_ID, 400);
        }

        // Retrieve user object
        if (! $user = User::embed(Request::get('embed'))->find($id)) {
            return response('User not found.', 404);
        }

        $user->applyEmbedableAttributes(Request::get('embed'));

        return $user;
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
