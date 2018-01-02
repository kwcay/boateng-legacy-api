<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Commands\Backup\Raw;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Dump extends Raw
{
    /**
     * The number of backup files to keep.
     *
     * @const int
     */
    const NUM_BACKUPS = 90;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:raw-dump
                            {--force : force execution even if config is disabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps MySQL tables to file.';

    /**
     * Execute the console command.
     *
     * @todo   Ideally, the users and oauth_* tables should be backed up separately, and maybe encrypted.
     * @return mixed
     */
    public function handle()
    {
        if (! $this->shouldRun() && ! $this->option('force')) {
            $this->comment('SQL dumps disabled, exiting.');

            return 0;
        }

        $host       = config('database.connections.mysql.host');
        $port       = config('database.connections.mysql.port');
        $user       = config('database.connections.mysql.username');
        $password   = config('database.connections.mysql.password');
        $database   = config('database.connections.mysql.database');
        $charset    = config('database.connections.mysql.charset', 'UTF8');

        if (! $host || ! $port || ! $user || ! $password || ! $database) {
            return $this->error('Invalid database parameters.');
        }

        // Build command
        $builder = ProcessBuilder::create()
            ->setPrefix('mysqldump')
            ->add(sprintf('--host=%s', $host))
            ->add(sprintf('--port=%u', $port))
            ->add(sprintf('--user=%s', $user))
            ->add(sprintf('--password=%s', $password))
            ->add(sprintf('--default-character-set=%s', $charset))
            ->add('--no-create-db')
            ->add('--set-charset')
            ->add('--extended-insert')
            ->add('--skip-lock-tables')
            ->add($database);

        $process = $builder->getProcess();

        $this->comment('No tables specified, backing up all tables.');

        // Run command
        $this->info('Running backup...');

        if ($process->run() !== 0) {
            throw new ProcessFailedException($process);
        }

        // Dump to file
        $filename = $this->generateFileName('DoraBoatengDump').'.sql';

        $this->info('Dumping to "'.$filename.'"...');

        if (! $this->compressTo(static::PATH, $filename, $process->getOutput())) {
            return $this->error('Backup failed.');
        }

        // Remove excess backup files.
        $files      = $this->store->files(static::PATH);
        $numFiles   = count($files);

        if ($numFiles <= static::NUM_BACKUPS) {
            $this->comment($numFiles.' is under the backup limit of '.static::NUM_BACKUPS.' files, keeping them all.');
        } else {
            $remove = array_slice($files, 0, $numFiles - static::NUM_BACKUPS);

            $this->info('Removing '.count($remove).' backup files...');

            $this->store->delete($remove);
        }

        $this->info('Done.');

        return 0;
    }

    /**
     * @return bool
     */
    protected function shouldRun() : bool
    {
        return (bool) config('app.backups.sql-dump');
    }
}
