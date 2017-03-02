<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
namespace App\Factories;

/**
 * Factory contract.
 */
abstract class Contract
{
    protected $isBooted = false;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * Called once class has been instantiated.
     */
    public function boot() {}

    /**
     * @param string $msg
     */
    public function setMessage($msg)
    {
        array_push($this->messages, $msg);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
