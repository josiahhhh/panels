<?php

namespace Pterodactyl\Console\Commands\Iceline;

use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Iceline\BackupSettings;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Backups\DeleteBackupService;
use SplFileInfo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class DeleteBackupsCommand extends Command
{

    /**
     * @var string
     */
    protected $description = 'Prunes backups that are over their server\'s retention period';

    /**
     * @var \Pterodactyl\Services\Backups\DeleteBackupService
     */
    private $deleteBackupService;

    /**
     * @var string
     */
    protected $signature = 'p:iceline:delete-backups';

    /**
     * CleanServiceBackupFilesCommand constructor.
     *
     * @param \Illuminate\Contracts\Filesystem\Factory $filesystem
     */
    public function __construct(DeleteBackupService $deleteBackupService) {
        parent::__construct();

        $this->deleteBackupService = $deleteBackupService;
    }

    /**
     * Handle command execution.
     */
    public function handle() {
        foreach (Server::all() as $server) {
            /** @var BackupSettings $backupSettings */
            $backupSettings = BackupSettings::where('server_id', $server->id)->first();

            // Skip if there are no retention settings
            if ($backupSettings == null) {
                continue;
            }

            // Skip if retention is infinite
            if ($backupSettings->backup_retention <= 0) {
                continue;
            }

            Log::info('processing backup auto-delete for server', [
                'server' => $server->id
            ]);

            $backups = Backup::where('server_id', $server->id)->get();

            /** @var Backup $backup */
            foreach ($backups as $backup) {
                $expired = \Illuminate\Support\Carbon::now()->gt($backup->completed_at->addSeconds($backupSettings->backup_retention));

                Log::info('processing backup auto-delete for backup', [
                    'server' => $server->id,
                    'backup' => $backup->id,
                    'backup_retention' => $backupSettings->backup_retention,
                    'expired' => $expired
                ]);

                try {
                    if ($expired) {
                        $this->deleteBackupService->handle($backup);

                        Log::info('auto-deleted expired backup');
                    }
                } catch (\Exception $ex) {
                    Log::error('failed to automatically delete backup that surpassed it\'s retention period', [
                        'ex' => $ex
                    ]);
                }
            }
        }
    }
}
