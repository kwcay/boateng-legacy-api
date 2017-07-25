<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 *
 * @version 0.4
 * @brief   Handles culture-related API requests.
 */
namespace App\Http\Controllers\v0_5;

use App\Http\Requests;
use App\Models\Culture;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\v0_5\Controller as BaseController;

class CultureController extends BaseController
{
    /**
     * Stores a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        // Unobfuscate language ID.
        if ($this->request->has('language_id')) {
            $encodedId = $this->request->input('language_id');
            $this->request->merge(['language_id' => Language::decodeId($encodedId)]);
        }

        // Validate incoming data.
        $this->validate($this->request, (new Culture)->validationRules);

        // Create culture record.
        $culture = Culture::create($this->getAttributesFromRequest());

        return $culture;
    }
}
