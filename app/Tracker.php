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
    private $trackedEvents = [];

    /**
     * @param string $projectId
     * @param string $masterKey
     * @param string $writeKey
     * @param string $readKey
     */
    public function __construct($projectId, $masterKey, $writeKey, $readKey)
    {
        $this->keen = KeenIOClient::factory([
            'projectId' => $projectId,
            'masterKey' => $masterKey,
            'writeKey'  => $writeKey,
            'readKey'   => $readKey,
        ]);
    }

    /**
     * Tracks API events.
     *
     * @param  string $event
     * @param  array  $parameters
     * @return static
     */
    public function addEvent($event, array $parameters = [])
    {
        $this->trackedEvents[$event][] = $parameters;

        return $this;
    }

    /**
     * Saves event data.
     *
     * @return void
     */
    public function persist()
    {
        if (! $this->trackedEvents) {
            return;
        }

        $this->tracker->addEvents($this->trackedEvents);
    }
}
