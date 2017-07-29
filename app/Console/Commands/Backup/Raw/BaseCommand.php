<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Commands\Backup\Raw;

use Storage;
use Illuminate\Console\Command;
use App\Console\Traits\FileTrait;

class BaseCommand extends Command
{
    use FileTrait;

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
    protected function shouldRun()
    {
        return (bool) config('app.backups.sql-dump');
    }
}
