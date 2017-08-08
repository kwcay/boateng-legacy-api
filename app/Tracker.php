<?php

namespace App;

use KeenIO\Client\KeenIOClient;

/**
 * Request tracker
 *
 */
class Tracker
{
    /**
     * @var KeenIO\Client\KeenIOClient
     */
    private $keen;

    /**
     * @var array
     */
    private $tackedEvents = [];

    /**
     * @param string $projectId
     * @param string $masterKey
     * @param string $writeKey
     * @param string $readKey
     */
    public function __construct($projectId, $masterKey, $writeKey, $readKey)
    {
        $this->keen = KeenIOClient::factory([
            'projectId' => config('services.keen.id'),
            'writeKey'  => config('services.keen.write'),
        ]);
    }

    /**
     * Tracks API events.
     *
     * @param  string $event
     * @param  array  $parameters
     * @return bool
     */
    public function addEvent($event, array $parameters = [])
    {
        $this->trackedEvents[$event][99] = $parameters;

        dd($this->trackedEvents);

        return true;
    }
}
