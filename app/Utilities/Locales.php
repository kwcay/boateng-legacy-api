<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Utilities;

class Locales
{
    /**
     * List of supported locales.
     *
     * @const array
     */
    const SUPPORTED_LOCALES = [
        'en-CA' => 'English Canada',
        'en-US' => 'English United States',
        'fr-CA' => 'Français Canada',
        'fr-FR' => 'Français France',
    ];

    /**
     * List of supported ISO 639-3 codes.
     *
     * @const array
     */
    const SUPPORTED_LANGUAGES = [
        'eng'   => 'English',
        'fra'   => 'Français',
    ];

    /**
     * Maps locales to their respective ISO 639-3 language codes.
     *
     * @const array
     */
    const LOCALES_LANGUAGE_MAP = [
        'en-CA' => 'eng',
        'en-US' => 'eng',
        'fr-CA' => 'fra',
        'fr-FR' => 'fra',
    ];

    public static function all()
    {
        $all = self::SUPPORTED_LOCALES + self::SUPPORTED_LANGUAGES;

        ksort($all);

        return $all;
    }

    public static function allKeys()
    {
        return array_values(array_flip(self::all()));
    }
}
