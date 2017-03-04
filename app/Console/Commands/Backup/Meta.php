<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Console\Commands\Backup;

use Storage;
use Exception;
use Illuminate\Console\Command;
use App\Factories\BackupFactory;
use App\Console\Traits\FileTrait;

class Meta extends Command
{
    use FileTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:meta {file? : The relative path to the backup file}';

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
    public function __construct()
    {
        parent::__construct();

        $this->storage = Storage::disk('backups');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $filename = $this->getFilenameFromAgrs()) {
            return 0;
        }

        // TODO: Create a FilesystemAdapter that extends League\Flysystem\Adapter\Local
        $filename = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$filename);

        if (! $meta = $this->getPharMetaData($filename)) {
            return 0;
        }

        // General backup meta
        $this->line('');
        $this->info("Backup ID:\t{$meta['id']}");
        ! isset($meta['date']) ?: $this->info("Backed-up on:\t{$meta['date']}");
        $this->info("Data format:\t{$meta['format']}");
        $this->info("Checksum:\t{$meta['checksum-method']}");

        // Resource info
        $rows = [];
        $headers = ['Resource', 'Count'];

        foreach ($meta as $resourceName => $info) {
            if (! is_array($info) || ! isset($info['files']) || ! is_int($info['files'])) {
                continue;
            }

            $rows[] = [$resourceName, $info['files']];
        }

        $this->line('');
        $this->table($headers, $rows);

        return 1;
    }
}
