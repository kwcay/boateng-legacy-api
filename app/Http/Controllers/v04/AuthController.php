<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 *
 * @version 0.4
 * @brief   Handles authentication-related API requests.
 */
namespace App\Http\Controllers\v0_4;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller as BaseController;

class AuthController extends BaseController
{
    /**
     * @param Illuminate\Http\Request $request
     * @param Illuminate\Http\Response $response
     * @return void
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request  = $request;
        $this->response = $response;
    }

    /**
     * Authenticates a user
     *
     * @return Response
     */
    public function login()
    {
        $username = $this->request->get('username');
        $password = $this->request->get('password');

        return [$username, $password];

        return response('Not Implemented.', 501);
    }

    /**
     * Checks if a user is authenticated
     *
     * @param string $id    Unique ID of tag.
     * @return object
     */
    public function check($id)
    {
        return response('Not Implemented.', 501);
    }

    /**
     * Un-authenticates a user
     *
     * @return Response
     */
    public function logout()
    {
        return response('Not Implemented.', 501);
    }
}
