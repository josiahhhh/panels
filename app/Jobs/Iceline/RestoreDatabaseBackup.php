<?php

namespace Pterodactyl\Jobs\Iceline;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use obregonco\B2\Client;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Iceline\BackupRestoreStatus;
use Pterodactyl\Models\Iceline\DatabaseBackup;
use Pterodactyl\Models\Server;

class RestoreDatabaseBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Contains the database backup for the job.
     * @var DatabaseBackup
     */
    protected $databaseBackup;

    /**
     * Create a new job instance.
     *
     * @param DatabaseBackup $databaseBackup
     * @param Encrypter $encrypter
     */
    public function __construct(DatabaseBackup $databaseBackup)
    {
        $this->databaseBackup = $databaseBackup;
    }

    /**
     * Execute the job.
     *
     * @param Encrypter $encrypter
     * @return void
     * @throws \Exception
     */
    public function handle(Encrypter $encrypter)
    {
        $status = new BackupRestoreStatus();
        $status->server_id = $this->databaseBackup->server_id;
        $status->type = 'database';
        $status->database_backup_id = $this->databaseBackup->id;
        $status->save();

        /** @var Database $database */
        $database = $this->databaseBackup->database;

        $filename = '/tmp/database_backup_' . date('G_a_m_d_y') . '.sql';

        $client = new \obregonco\B2\Client(config('backups.disks.b2.key_id'), [
            'applicationKey' => config('backups.disks.b2.application_key'),
        ]);

        $file = sprintf('%s/%s.sql',
            $this->databaseBackup->server->uuid,
            $this->databaseBackup->uuid);

        // Download the sql backup
        try {
            $client->download([
                'FileName' => $file,
                'BucketName' => config('backups.disks.b2.database_bucket_name'),
                'SaveAs' => $filename
            ]);
        } catch (Exception $ex) {
            $status->error = $ex->getMessage();
            $status->completed_at = Carbon::now();
            $status->save();

            Log::error('failed to download database backup to restore', [
                'id' => $this->databaseBackup->id,
                'server_id' => $this->databaseBackup->server_id,
                'backup_id' => $this->databaseBackup->database_id,
                'ex' => $ex
            ]);

            throw new \Exception('error downloading database dump: ' . $ex->getMessage());
        }

        // Restore the backup to the database
        $cmd = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=\'%s\' --database=%s --one-database %s 2>&1 < %s',
            $database->host->host,
            $database->host->port,
            $database->username,
            $encrypter->decrypt($database->password),
            $database->database,
            $database->database,
            $filename);
        Log::info('mysql: ' . $cmd);

        $output = null;
        exec($cmd, $output, $result);

        if ($result > 0) {
            $status->error = $output;
            $status->completed_at = Carbon::now();
            $status->save();

            Log::error('failed to restore database backup', [
                'id' => $this->databaseBackup->id,
                'server_id' => $this->databaseBackup->server_id,
                'backup_id' => $this->databaseBackup->database_id,
                'code' => $result,
                'output' => implode(';', $output)
            ]);

            throw new \Exception('error restoring database dump: exit code ' . $result . ' ' . implode('; ', $output));
        }

        $status->is_successful = true;
        $status->completed_at = Carbon::now();
        $status->save();

        // Delete the restore status when completed
        $status->delete();

        // Remove the temporary file
        if (!unlink($filename)) {
            Log::error('failed to delete temporary database backup file', ['error' => error_get_last()]);
        }
    }

    /**
     * The job failed to process.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    public function failed(\Exception $exception)
    {
        // Send user notification of failure, etc...

        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }
    }
}
