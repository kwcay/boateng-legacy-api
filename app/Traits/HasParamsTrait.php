<?php
/**
 * Copyright Dora Boateng(TM) 2015, all rights reserved.
 */
namespace App\Traits;

use Illuminate\Support\Arr;

trait HasParamsTrait
{
    /**
     * Checks if a parameter key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasParam($key)
    {
        return Arr::has($this->params, $key);
    }

    /**
     * Retrieves a parameter value.
     *
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function getParam($key, $default = '')
    {
        return Arr::get($this->params, $key, $default);
    }

    /**
     * Sets a parameter value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setParam($key, $value)
    {
        $params = $this->params;
        $old = Arr::get($params, $key, null);
        Arr::set($params, $key, $value);
        $this->params = $params;

        return $old;
    }
}
