<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

abstract class Contract
{
    /**
     *
     */
    public function create();
    
    /**
     * Imports a data set into the database.
     *
     * @param   array $data
     * @return  App\Services\Backup\Contract
     */
    public function restore(array $data);
}
