<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
})->define(App\Models\Definition::class, function (Faker\Generator $faker) {
    return [
        'type' => \App\Models\Definition::TYPES[rand(0, count(\App\Models\Definition::TYPES) - 1)],
    ];
})->define(App\Models\Language::class, function (Faker\Generator $faker) {
    return [
        'code' => $faker->languageCode,
        'name' => 'Language Name',
    ];
});
