<?php

namespace App\Console\Commands\Backup\Raw;

use App\Console\Traits\FileTrait;
use App\Console\Commands\Contract;
use Illuminate\Support\Facades\Storage;

abstract class Raw extends Contract
{
    use FileTrait;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $store;

    /**
     * @const string
     */
    const PATH = 'dumps';

    public function __construct()
    {
        parent::__construct();

        $this->store = Storage::disk('backups');
    }

    /**
     * @return bool
     */
    protected function shouldRun() : bool
    {
        return (bool) config('app.backups.sql-dump');
    }
}
