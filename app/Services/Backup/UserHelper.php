<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services\Backup;

use App\Models\User;
use App\Services\Backup\Contract;

class UserHelper extends Contract
{
    /**
     *
     */
    public function create() {
        throw new \Exception('UserHelper::create not implemented.');
    }

    /**
     * Imports a data set into the database.
     *
     * @param   array $dataSet
     * @return  App\Services\Backup\UserHelper
     */
    public function restore(array $dataSet = null)
    {
        // Loop through dataset and import each model one by one.
        $saved = $skipped = 0;
        foreach ($dataSet as $data) {
            // Performance check.
            // TODO: this would be more efficient if we only retrieved the ID.
            if (! array_key_exists('email', $data) || User::findByEmail($data['email'])) {
                $skipped++;
                continue;
            }

            // User attributes.
            $attributes = [
                'uri'           => array_get($data, 'uri', ''),
                'name'          => array_get($data, 'name', ''),
                'email'         => array_get($data, 'email', ''),
                'password'      => array_get($data, 'password', ''),
                'params'        => array_get($data, 'params', ''),
                'created_at'    => array_get($data, 'createdAt', null),
                'deleted_at'    => array_get($data, 'deletedAt', null),
            ];

            // Create model.
            $user = User::create($attributes);

            // TODO: roles ?
            // TODO: OAuth clients ?

            $saved++;
        }

        $this->setMessage($saved.' of '.($saved + $skipped).' users updated.');

        return $this;
    }
}
