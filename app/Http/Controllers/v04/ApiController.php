<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Http\Controllers\v0_4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * Returns the version number of the API.
     */
    public function version()
    {
        return 'Dora Boateng API 0.4';
    }
}
