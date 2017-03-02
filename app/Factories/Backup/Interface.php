<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Factories\Backup;

interface HelperInterface
{
    /**
     * @var array
     */
    protected $messages = [];

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
