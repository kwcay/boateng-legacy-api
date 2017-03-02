<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Factories\Backup;

abstract class Contract
{
    /**
     *
     */
    public function create();

    /**
     *
     */
    public function restore(array $data);
}
