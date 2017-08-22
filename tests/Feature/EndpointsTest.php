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
     * API version
     *
     * @todo  Retrieve from config
     * @const int
     */
    const API_VERSION = '0.5';

    /**
     * The root should redirect to the proper version path (/ => /API_VERSION).
     *
     * @return void
     */
    public function testRoot()
    {
        $response = $this->get('/');

        $response->assertRedirect('/'.self::API_VERSION);
    }

    /**
     *
     */
    public function testUnauthorizedResponse()
    {
        $resources = ['definitions', 'languages'];

        foreach ($resources as $resource) {
            $this->get('/'.self::API_VERSION.'/'.$resource, [])->assertStatus(401);
            $this->post('/'.self::API_VERSION.'/'.$resource, [])->assertStatus(401);
            $this->put('/'.self::API_VERSION.'/'.$resource.'/999', [])->assertStatus(401);
            $this->patch('/'.self::API_VERSION.'/'.$resource.'/999', [])->assertStatus(401);
            $this->delete('/'.self::API_VERSION.'/'.$resource.'/999', [])->assertStatus(401);
        }
    }

    /**
     * Each resource should have a count endpoint.
     *
     * @dataProvider getCountTestData()
     */
    public function testCount($endpoint, $expected)
    {
        $this->markTestIncomplete('TODO: mock OAuth authentication.');

        $response = $this->get($endpoint);

        $response->assertStatus($expected);
    }

    /**
     * @todo   Somehow mock OAuth authentication
     * @return array
     */
    public function getCountTestData()
    {
        $format = '/'.self::API_VERSION.'/%s/count';

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
        ];
    }
}
