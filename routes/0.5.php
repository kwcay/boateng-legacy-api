<?php

// Info
Route::get('/', 'ApiController@version');
Route::get('/locales', 'ApiController@locales');

// Culture endpoints.
Route::get('cultures/count',            'CultureController@count');
Route::get('cultures/search',           'CultureController@search');
Route::resource('cultures',             'CultureController', ['only' => ['index', 'show']]);
Route::post('cultures',                 'CultureController@store')->middleware('write');
Route::put('cultures/{id}',             'CultureController@update')->middleware('write');
Route::delete('cultures/{id}',          'CultureController@delete')->middleware('write');
Route::options('cultures/{id?}',        'ApiController@options')->middleware('write');

// Definition endpoints.
Route::get('definitions/count',         'DefinitionController@count');
Route::get('definitions/daily/{type?}', 'DefinitionController@getDaily');
Route::get('definitions/random/{lang?}', 'DefinitionController@random');
Route::get('definitions/search/{query?}', 'DefinitionController@search');
Route::get('definitions/title/{title}', 'DefinitionController@findBytitle');
Route::resource('definitions',          'DefinitionController', ['only' => ['index', 'show']]);
Route::post('definitions',              'DefinitionController@store')->middleware('write');
Route::put('definitions/{id}',          'DefinitionController@update')->middleware('write');
Route::patch('definitions/{id}',        'DefinitionController@update')->middleware('write');
Route::delete('definitions/{id}',       'DefinitionController@destroy')->middleware('write');
Route::options('definitions/{id?}',     'ApiController@options')->middleware('write');

// Language endpoints.
Route::get('languages/count',           'LanguageController@count');
Route::get('languages/search',          'LanguageController@search');
Route::get('languages/weekly',          'LanguageController@getWeekly');
Route::get('languages/auto',            'LanguageController@autocomplete');
Route::resource('languages',            'LanguageController', ['only' => ['index', 'show']]);
Route::post('languages',                'LanguageController@store')->middleware('write');
Route::put('languages/{language}',      'LanguageController@update')->middleware('write');
Route::patch('languages/{language}',    'LanguageController@update')->middleware('write');
Route::delete('languages/{language}',   'LanguageController@delete')->middleware('write');
Route::options('languages/{language?}', 'ApiController@options')->middleware('write');

// Reference endpoints
Route::get('references/count',          'ReferenceController@count');
Route::get('references/search',         'ReferenceController@search');
Route::resource('references',           'ReferenceController', ['only' => ['index', 'show']]);
Route::post('references',               'ReferenceController@store')->middleware('write');
Route::put('references/{id}',           'ReferenceController@update')->middleware('write');
Route::delete('references/{id}',        'ReferenceController@delete')->middleware('write');
Route::options('references/{id?}',      'ApiController@options')->middleware('write');

// Tag endpoints
Route::get('tags/count',                'TagController@count');
Route::get('tags/search',               'TagController@search');
Route::resource('tags',                 'TagController', ['only' => ['index', 'show']]);
Route::post('tags',                     'TagController@store')->middleware('write');
Route::put('tags/{id}',                 'TagController@update')->middleware('write');
Route::delete('tags/{id}',              'TagController@delete')->middleware('write');
Route::options('tags/{id?}',            'ApiController@options')->middleware('write');

// User endpoints
Route::get('user',                      'UserController@current')->middleware('write');

// General lookup
Route::get('search/{query}',            'ApiController@generalSearch');
Route::get('latest',                    'ApiController@latest');
