<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Services;

use App\Services\Contract;
use Jenssegers\Optimus\Optimus;

class ObfuscatorService extends Contract
{
    /**
     * @var Jenssegers\Optimus\Optimus
     */
    private $obfuscator;

    public function __construct()
    {
        $this->obfuscator = new Optimus(794923373, 757342309, 1069650690);
    }

    /**
     * Returns an obfuscated ID
     *
     * @param int
     * @return string
     */
    public function encode($id)
    {
        return $this->obfuscator->encode($id);
    }

    /**
     * Returns the original  ID
     *
     * @param string $obfuscatedId
     * @return int
     */
    public function decode($obfuscatedId)
    {
        return $this->obfuscator->decode($obfuscatedId);
    }
}
