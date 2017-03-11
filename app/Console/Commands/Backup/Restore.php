<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
namespace App\Console\Commands\Backup;

use Storage;
use Exception;
use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Console\Traits\FileTrait;

class Restore extends Command
{
    use FileTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore
                            {file? : The relative path to the backup file}
                            {--R|refresh-db : Refresh migrations before restoring the backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restores a backup file.';

    /**
     * Unique id for this restore process.
     *
     * @var string
     */
    protected $restoreId;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BackupService $helper)
    {
        parent::__construct();

        $this->helper   = $helper;
        $this->storage  = Storage::disk('backups');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Retrieve backup filename.
        if (! $filename = $this->getFilenameFromAgrs()) {
            return 0;
        }

        // Confirm backup restore.
        if (! $this->confirm('Are you sure you want to restore the backup file "'.$filename.'"?')) {
            return 0;
        }

        // Restore backup file.
        $this->info('Reading backup file...');
        try {
            $this->helper->restore($filename, [
                'refresh-db' => $this->option('refresh-db'),
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->line('Aborting...');

            return 0;
        }

        return 1;
    }
}
