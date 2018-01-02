<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Commands\Backup\Raw;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Sync extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads the latest available raw production data into the local database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (app()->environment() !== 'local') {
            return $this->error('Backup sync script can only be run locally.');
        }

        // TODO: get base path from storage driver.
        $host       = config('app.automation.production-server');
        $backupsDir = config('app.automation.live-directory').'/storage/backups/'.static::PATH;

        $this->info('Connecting to remote...');

        $process = new Process("ssh {$host} ls -rt {$backupsDir} | tail -n1");

        if ($process->run() !== 0) {
            throw new ProcessFailedException($process);
        }

        if (! $backupFile = preg_replace('/\s+/', '', $process->getOutput())) {
            return $this->error('No backup files found.');
        }

        $this->info('Downloading '.$backupFile.'...');

        $localDir   = storage_path('backups/'.static::PATH);
        $process    = new Process("scp {$host}:{$backupsDir}/{$backupFile} {$localDir}/{$backupFile}");

        if ($process->run() !== 0) {
            throw new ProcessFailedException($process);
        }

        $this->info('Reading '.$backupFile.'...');

        try {
            if (! $data = $this->store->get(static::PATH.'/'.$backupFile)) {
                return $this->error('Could not read backup file.');
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        $this->store->put(static::PATH.'/raw-dump.sql', gzuncompress($data));

        $this->info('Loading backup file...');

        $this->comment('TODO: confirm before overwriting everything... since all tables will be reloaded.');
    }
}
