<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */

//
Route::get('/', 'ApiController@version');

// Culture endpoints.
Route::get('cultures/count',            'CultureController@count');
Route::get('cultures/search/{query}',   'CultureController@search');
Route::resource('cultures',             'CultureController', ['only' => ['index', 'show']]);
Route::post('cultures',                 'CultureController@store')->middleware('auth:api');
Route::put('cultures/{id}',             'CultureController@update')->middleware('auth:api');
Route::delete('cultures/{id}',          'CultureController@delete')->middleware('auth:api');
Route::options('cultures/{id?}',        'ApiController@options')->middleware('auth:api');

// Definition endpoints.
Route::get('definitions/count',         'DefinitionController@count');
Route::get('definitions/daily/{type?}', 'DefinitionController@getDaily');
Route::get('definitions/random/{lang?}', 'DefinitionController@random');
Route::get('definitions/search/{query}', 'DefinitionController@search');
Route::get('definitions/title/{title}', 'DefinitionController@findBytitle');
Route::resource('definitions',          'DefinitionController', ['only' => ['index', 'show']]);
Route::post('definitions',              'DefinitionController@store')->middleware('auth:api');
Route::put('definitions/{id}',          'DefinitionController@update')->middleware('auth:api');
Route::delete('definitions/{id}',       'DefinitionController@delete')->middleware('auth:api');
Route::options('definitions/{id?}',     'ApiController@options')->middleware('auth:api');

// Language endpoints.
Route::get('languages/count',           'LanguageController@count');
Route::get('languages/search/{query}',  'LanguageController@search');
Route::get('languages/weekly',          'LanguageController@getWeekly');
Route::resource('languages',            'LanguageController', ['only' => ['index', 'show']]);
Route::post('languages',                'LanguageController@store')->middleware('auth:api');
Route::put('languages/{language}',      'LanguageController@update')->middleware('auth:api');
Route::delete('languages/{language}',   'LanguageController@delete')->middleware('auth:api');
Route::options('languages/{language?}', 'ApiController@options')->middleware('auth:api');

// Reference endpoints
Route::get('references/count',          'ReferenceController@count');
Route::get('references/search/{query}', 'ReferenceController@search');
Route::resource('references',           'ReferenceController', ['only' => ['index', 'show']]);
Route::post('references',               'ReferenceController@store')->middleware('auth:api');
Route::put('references/{id}',           'ReferenceController@update')->middleware('auth:api');
Route::delete('references/{id}',        'ReferenceController@delete')->middleware('auth:api');
Route::options('references/{id?}',      'ApiController@options')->middleware('auth:api');

// Tag endpoints
Route::get('tags/count',                'TagController@count');
Route::get('tags/search/{query}',       'TagController@search');
Route::resource('tags',                 'TagController', ['only' => ['index', 'show']]);
Route::post('tags',                     'TagController@store')->middleware('auth:api');
Route::put('tags/{id}',                 'TagController@update')->middleware('auth:api');
Route::delete('tags/{id}',              'TagController@delete')->middleware('auth:api');
Route::options('tags/{id?}',            'ApiController@options')->middleware('auth:api');

// User endpoints
Route::get('user',                      'UserController@current')->middleware('auth:api');

// General lookup
Route::get('search/{query}',            'ApiController@generalSearch');
Route::get('latest',                    'ApiController@latest');
