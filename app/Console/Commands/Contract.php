<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

abstract class Contract extends Command
{
    /**
     * @return bool
     */
    abstract protected function shouldRun() : bool;

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return int
     */
    public function error($string, $verbosity = null)
    {
        parent::error($string, $verbosity);

        return 1;
    }
}
