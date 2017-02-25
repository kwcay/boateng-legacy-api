<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GeneralController extends Controller
{
    /**
     * Redirects request to latest API
     */
    public function latest()
    {
        return redirect('/'. config('app.version'));
    }
}
