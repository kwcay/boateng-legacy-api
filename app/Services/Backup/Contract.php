<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

abstract class Contract
{
    /**
     * @var array
     */
    protected $messages = [];

    /**
     *
     */
    abstract public function create();

    /**
     * Imports a data set into the database.
     *
     * @param   array $data
     * @return  App\Services\Backup\Contract
     */
    abstract public function restore(array $data);

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
