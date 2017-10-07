<?php

namespace App\Http\Controllers\v05;

use App\Models\Culture;
use App\Models\Language;
use App\Http\Controllers\v05\Controller as BaseController;

class CultureController extends BaseController
{
    /**
     * Stores a newly created resource in storage.
     *
     * @return Culture
     */
    public function store()
    {
        // Unobfuscate language ID.
        if ($this->request->has('language_id')) {
            $encodedId = $this->request->input('language_id');
            $this->request->merge(['language_id' => Language::decodeId($encodedId)]);
        }

        // Validate incoming data.
        $model = new Culture;
        $this->validate($this->request, $model->validationRules);

        // Create culture record.
        $culture = $model->create($this->getAttributesFromRequest());

        return $culture;
    }
}
