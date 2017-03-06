<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
namespace App\Traits;

use App;

trait TransliteratableTrait
{
    /**
     * Defines the transliteration relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transliterations()
    {
        return $this->morphMany('App\Models\Transliteration', 'parent');
    }

    /**
     * Shortcut to save many transliterations.
     *
     * @param array $transliterations
     * @param bool $replace
     * @return ??
     */
    public function saveTransliterations(array $transliterations, $replace = true)
    {
        // Clear current transliterations.
        if ($replace && count($this->transliterations)) {
            foreach ($this->transliterations as $trans) {
                $trans->delete();
            }
        }

        // Add new transliterations.
        foreach ($transliterations as $trans) {
            // TODO: default to current locale for strings.
            if (is_string($trans)) {
                $this->transliterations()->create([
                    'language' => 'eng',
                    'transliteration' => $trans
                ]);
            }

            //
            else {
                $this->transliterations()->create(array_only((array) $trans, [
                    'language',
                    'transliteration',
                    'createdAt',
                    'deletedAt',
                ]));
            }
        }
    }

    /**
     * Accessor for $this->transliteration
     *
     * TODO
     */
    public function getTransliterationAttribute()
    {
        return '';

        $trans = $this->transliterations->where('language', App::locale())->get();
    }

    /**
     * Accessor for $this->arTransliteration
     */
    public function getArTransliterationAttribute($trans = '')
    {
        return '';
    }

    /**
     * Accessor for $this->deTransliteration
     */
    public function getDeTransliterationAttribute($trans = '')
    {
        return '';
    }

    /**
     * Accessor for $this->duTransliteration
     */
    public function geDuTransliterationAttribute($trans = '')
    {
        return '';
    }

    /**
     * Accessor for $this->enTransliteration
     */
    public function getEnTransliterationAttribute($trans = '')
    {
        return '';
    }

    /**
     * Accessor for $this->esTransliteration
     */
    public function getEsTransliterationAttribute($trans = '')
    {
        return '';
    }

    /**
     * Accessor for $this->frTransliteration
     */
    public function getFrTransliterationAttribute($trans = '')
    {
        return '';
    }

    /**
     * Accessor for $this->ptTransliteration
     */
    public function getPtTransliterationAttribute($trans = '')
    {
        return '';
    }
}
