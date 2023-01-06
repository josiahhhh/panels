<?php

namespace Pterodactyl\Console\Commands\Iceline;

use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Iceline\BackupSettings;
use Pterodactyl\Models\Iceline\DatabaseBackup;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Backups\DeleteBackupService;
use SplFileInfo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class DeleteFailedBackupsCommand extends Command
{

    /**
     * @var string
     */
    protected $description = 'Deletes old failed backup records.';

    /**
     * @var \Pterodactyl\Services\Backups\DeleteBackupService
     */
    private $deleteBackupService;

    /**
     * @var string
     */
    protected $signature = 'p:iceline:delete-failed-backups';

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
            // Retrieve all the failed backups
            $backups = Backup::where('server_id', $server->id)
                ->where('is_successful', 1)
                ->whereNotNull('completed_at')
                ->get();

            /** @var Backup $backup */
            foreach ($backups as $backup) {
                // Check if the failed backup is over 72h old
                $expired = \Illuminate\Support\Carbon::now()->gt($backup->completed_at->addHours(72));

                try {
                    if ($expired) {
                        Log::info('processing backup auto-delete for failed backup', [
                            'server' => $server->id,
                            'backup' => $backup->id,
                            'expired' => $expired
                        ]);

                        $this->deleteBackupService->handle($backup);

                        Log::info('auto-deleted expired failed backup', [
                            'server' => $server->id,
                            'backup' => $backup->id
                        ]);

                    }
                } catch (\Exception $ex) {
                    Log::error('failed to automatically delete failed backup that surpassed it\'s retention period', [
                        'ex' => $ex
                    ]);
                }
            }

            // Retrieve all the failed database backups
            $databaseBackups = DatabaseBackup::where('server_id', $server->id)
                ->where('is_successful', 1)
                ->whereNotNull('completed_at')
                ->get();

            /** @var DatabaseBackup $backup */
            foreach ($databaseBackups as $databaseBackup) {
                // Check if the failed backup is over 72h old
                $expired = \Illuminate\Support\Carbon::now()->gt($databaseBackup->completed_at->addHours(72));

                try {
                    if ($expired) {
                        Log::info('processing backup auto-delete for failed backup', [
                            'server' => $server->id,
                            'backup' => $databaseBackup->id,
                            'expired' => $expired
                        ]);

                        $this->deleteBackupService->handle($databaseBackup);

                        Log::info('auto-deleted expired failed backup', [
                            'server' => $server->id,
                            'backup' => $databaseBackup->id
                        ]);

                    }
                } catch (\Exception $ex) {
                    Log::error('failed to automatically delete failed backup that surpassed it\'s retention period', [
                        'ex' => $ex
                    ]);
                }
            }
        }
    }
}
