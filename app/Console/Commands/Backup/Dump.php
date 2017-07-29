<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Commands\Backup;

use Storage;
use Illuminate\Console\Command;
use App\Console\Traits\FileTrait;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Dump extends Command
{
    use FileTrait;

    /**
     * @const string
     */
    const PATH = 'dumps';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:dump
                            {--force : force execution even if config is disabled}
                            {--default-character-set=UTF8}
                            {--no-create-db=1}
                            {--set-charset=1}
                            {--extended-insert=1}
                            {--skip-lock-tables=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps MySQL tables to file.';

    /**
     * Command format: host, port, user, password
     */
    protected $command = 'mysqldump --host=%s --port=%u --user=%s';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->shouldRun() && ! $this->option('force')) {
            return $this->comment('SQL dumps disabled, exiting.');
        }

        $host       = env('DB_HOST');
        $port       = env('DB_PORT');
        $user       = env('DB_USERNAME');
        $password   = env('DB_PASSWORD');
        $database   = env('DB_DATABASE');

        if (! $host || ! $port || ! $user || ! $password || ! $database) {
            return $this->error('Invalid database parameters.');
        }

        // Build command
        $this->command = sprintf($this->command, $host, $port, $user);

        $this->addDumpOption('default-character-set', true);
        $this->addDumpOption('no-create-db');
        $this->addDumpOption('set-charset');
        $this->addDumpOption('extended-insert');
        $this->addDumpOption('skip-lock-tables');

        $this->command .= ' '.$database;

        $this->comment('No tables specified, backing up all tables.');

        // Run command
        $this->info('Running backup...');

        $this->info($this->command);
        $this->info($password);

        $input      = new InputStream;
        $process    = new Process($this->command);

        $process->setInput($input);
        $process->start();

        // Use an input stream to avoid writing the password in the console
        $input->write($password);
        $input->close();

        // Wait for the command to run
        $process->wait();

        if ($process->getExitCode() !== 0) {
            throw new ProcessFailedException($process);
        }

        // Dump to file
        $this->store    = Storage::disk('backups');
        $filename       = $this->generateFileName('DoraBoatengDump').'.sql';

        $this->info('Dumping to "'.$filename.'"...');

        if (! $this->compressTo(static::PATH, $filename, $process->getOutput())) {
            return $this->error('Backup failed.');
        }

        $this->comment('TODO: keep last 50 backups, remove anything else.');

        $this->info('Done.');
    }

    public function isEnabled()
    {
        return true;
    }

    /**
     * @param  string $option
     * @param  bool   $hasValue
     */
    protected function addDumpOption($option, $hasValue = false)
    {
        if ($value = $this->option($option)) {
            $this->command .= ' --'.$option.($hasValue ? '="'.$value.'"' : '');
        }
    }

    /**
     * @return bool
     */
    protected function shouldRun()
    {
        return (bool) config('app.backups.sql-dump');
    }
}
