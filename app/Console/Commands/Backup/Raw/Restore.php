<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Commands\Backup\Raw;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Restore extends Raw
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:raw:restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads the latest available raw production data into the local database.';

    /**
     * Execute the console command.
     *
     * @todo   Support different environments (if already in prod, no need to ssh).
     * @todo   Support supplying a raw dump file (maybe for prod?).
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirm('All tables will be dropped and reloaded. Continue?')) {
            $this->info('Aborting');
            return 0;
        }

        $this->info('Verifying database credentials...');

        $db = config('database.connections.mysql', []);
        if (! array_key_exists('database', $db) ||
            ! array_key_exists('username', $db) ||
            ! array_key_exists('password', $db)
        ) {
            return $this->error('Missing database credentials');
        }

        // TODO: handle database being on the same or different host than script.
        $process = new Process(sprintf(
            'mysql --user=%s --password=%s --host=192.168.10.10 %s',
            $db['username'],
            $db['password'],
            $db['database']
        ));

        if ($process->run() !== 0) {
            throw new ProcessFailedException($process);
        }

        // TODO: get base path from storage driver.
        $host       = config('app.automation.production-server');
        $backupsDir = config('app.automation.live-directory').'/storage/backups/'.static::PATH;

        $this->info('Connecting to remote...');

        $process = new Process("ssh {$host} 'ls -rt {$backupsDir} | tail -1'");

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

        // TODO: handle database being on the same or different host than script.
        $process = new Process(sprintf(
            'mysql --user=%s --password=%s --host=192.168.10.10 %s < %s',
            $db['username'],
            $db['password'],
            $db['database'],
            $localDir.'/raw-dump.sql'
        ));

        if ($process->run() !== 0) {
            throw new ProcessFailedException($process);
        }

        // Truncate some tables
        $this->comment(
            'TODO: truncate some tables'.PHP_EOL.
            'TRUNCATE TABLE boateng.oauth_access_tokens;'.PHP_EOL.
            'TRUNCATE TABLE boateng.oauth_auth_codes;'.PHP_EOL.
            'TRUNCATE TABLE boateng.oauth_clients;'.PHP_EOL.
            'TRUNCATE TABLE boateng.oauth_personal_access_clients;'.PHP_EOL.
            'TRUNCATE TABLE boateng.oauth_refresh_tokens;'.PHP_EOL.
            'TRUNCATE TABLE boateng.password_resets;'.PHP_EOL.
            'TRUNCATE TABLE boateng.users;'.PHP_EOL
        );

        $this->info('Done.');

        return 0;
    }
}
