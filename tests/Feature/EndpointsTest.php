<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EndpointsTest extends TestCase
{
    /**
     * The root should redirect to the proper version path (/ => /API_VERSION).
     *
     * @return void
     */
    public function testRoot()
    {
        $response = $this->get('/');

        $response->assertRedirect('/0.4');
    }

    /**
     * Each resource should have a count endpoint.
     *
     * @dataProvider getCountTestData()
     */
    public function testCount($endpoint, $expected)
    {
        $response = $this->get($endpoint);

        $response->assertStatus($expected);
    }

    /**
     * @return array
     */
    public function getCountTestData()
    {
        // TODO: get API version from config file
        $format = '/0.4/%s/count';

        return [
            'Cultures count' => [
                sprintf($format, 'cultures'),
                200
            ],
            'Definitions count' => [
                sprintf($format, 'definitions'),
                200
            ],
            'Languages count' => [
                sprintf($format, 'languages'),
                200
            ],
            'References count' => [
                sprintf($format, 'references'),
                200
            ],
            'Tags count' => [
                sprintf($format, 'tags'),
                200
            ],
        ];
    }
}
