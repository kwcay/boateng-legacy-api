<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */

//
Route::get('/', 'ApiController@version');

// Authentication endpoints
Route::post('auth', 'AuthController@login');

// Culture endpoints.
Route::get('cultures/count', 'CultureController@count');
Route::get('cultures/search/{query}', 'CultureController@search');
Route::resource('cultures', 'CultureController', ['except' => ['create', 'edit']]);

// Definition endpoints.
Route::get('definitions/count', 'DefinitionController@count');
Route::get('definitions/daily/{type?}', 'DefinitionController@getDaily');
Route::get('definitions/random/{lang?}', 'DefinitionController@random');
Route::get('definitions/search/{query}', 'DefinitionController@search');
Route::get('definitions/title/{title}', 'DefinitionController@findBytitle');
Route::resource('definitions', 'DefinitionController', ['except' => ['create', 'edit']]);
Route::options('definition/{id?}', 'ApiController@options');

// Language endpoints.
Route::get('languages/count', 'LanguageController@count');
Route::get('languages/search/{query}', 'LanguageController@search');
Route::get('languages/weekly', 'LanguageController@getWeekly');
Route::resource('languages', 'LanguageController', ['except' => ['create', 'edit']]);

// Reference endpoints
Route::get('references/count', 'ReferenceController@count');
Route::get('references/search/{query}', 'ReferenceController@search');
Route::resource('references', 'ReferenceController', ['except' => ['create', 'edit']]);

// Tag endpoints
Route::get('tags/count', 'TagController@count');
Route::get('tags/search/{query}', 'TagController@search');
Route::resource('tags', 'TagController', ['except' => ['create', 'edit']]);

// General lookup
Route::get('search/{query}', 'ApiController@generalSearch');
Route::get('latest', 'ApiController@latest');
