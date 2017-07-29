<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Commands\Backup\Raw;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Load extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:raw-load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads a MySQL dump file into the database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // TODO: get base path from storage driver.
        $host       = config('app.automation.production-server');
        $backupsDir = config('app.automation.live-directory').'/storage/backups/'.static::PATH;

        $this->info('Connecting to remote...');

        $process = new Process("ssh {$host} ls {$backupsDir} | tail -n1");

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

        if (! $data = $this->store->get(static::PATH.'/'.$backupFile)) {
            return $this->error('Could not read backup file.');
        }

        $sql = gzuncompress($data);

        $this->info('SQL length: '.strlen($sql));
    }
}
