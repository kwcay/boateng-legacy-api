<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Traits;

trait Hashable
{
    /**
     * Creates a hash from the current state, excluding relations.
     *
     * @return string
     */
    public function getHash()
    {
        return md5(json_encode(array_except($this->attributesToArray(), [
            'createdAt',
            'created_at',
            'updatedAt',
            'updated_at',
            'deletedAt',
            'deleted_at',
        ])));
    }
}
