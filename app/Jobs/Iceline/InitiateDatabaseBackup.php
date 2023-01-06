<?php

namespace Pterodactyl\Jobs\Iceline;

use Exception;
use obregonco\B2\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Pterodactyl\Models\Database;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Models\Iceline\DatabaseBackup;

class InitiateDatabaseBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set the job timeout (30 minutes)
    public $timeout = 1800;

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
        /** @var Database $database */
        $database = $this->databaseBackup->database;

        $filename = '/tmp/pterodactyl/database_backup_' . $database->id . '_' . $this->databaseBackup->id . '_' . date('G_a_m_d_y') . '.sql';

        $cmd = 'mysqldump %s --host=%s --port=%d --password=\'%s\' --user=%s --single-transaction >%s 2>&1';
        $sanitizedCmd = 'mysqldump %s --host=%s --port=%d --user=%s --single-transaction >%s 2>&1';

        $cmd = sprintf(
            $cmd,
            $database->database,
            $database->host->host,
            $database->host->port,
            $encrypter->decrypt($database->host->password),
            $database->host->username,
            $filename);

        $sanitizedCmd = sprintf(
            $sanitizedCmd,
            $database->database,
            $database->host->host,
            $database->host->port,
            $database->host->username,
            $filename);

        // Log::info('mysqldump: ' . $cmd);

        Log::info('running server database backup', [
            'server_id' => $database->server->id,
            'database_id' => $database->server->id,
            'cmd' => $sanitizedCmd
        ]);

        exec($cmd, $output, $result);

        if ($result > 0) {
            $this->databaseBackup->error = $output;
            $this->databaseBackup->completed_at = Carbon::now();
            $this->databaseBackup->save();

            Log::error('error creating server database backup', [
                'cmd' => $sanitizedCmd,
                'output' => $output,
                'result' => $result
            ]);

            throw new \Exception('error creating database dump: exit code ' . $result . ' ' . implode('; ', $output));
        }

        Log::info('initializing database backup b2 client', [
            'server_id' => $database->server->id,
            'database_id' => $database->server->id
        ]);

        $client = new Client(config('backups.disks.b2.key_id'), [
            'applicationKey' => config('backups.disks.b2.application_key'),
        ]);

        $file = sprintf('%s/%s.sql',
            $this->databaseBackup->server->uuid,
            $this->databaseBackup->uuid);

        // Upload the sql backup
        try {
            Log::info('starting upload of database backup', [
                'server_id' => $database->server->id,
                'database_id' => $database->server->id,
                'file' => $file,
                'bucket' => config('backups.disks.b2.database_bucket_name')
            ]);

            $client->upload([
                'FileName' => $file,
                'BucketName' => config('backups.disks.b2.database_bucket_name'),
                'FileContentType' => 'application/x-sql	',
                'Body' => fopen($filename, 'r')
            ]);
        } catch (Exception $ex) {
            $this->databaseBackup->error = $ex->getMessage();
            $this->databaseBackup->completed_at = Carbon::now();
            $this->databaseBackup->save();

            Log::error('error uploading server database backup', [
                'server_id' => $database->server->id,
                'database_id' => $database->server->id,
            ]);

            throw new \Exception('error uploading database dump: ' . $ex->getMessage());
        }

        $this->databaseBackup->bytes = filesize($filename);
        $this->databaseBackup->is_successful = true;
        $this->databaseBackup->completed_at = Carbon::now();
        $this->databaseBackup->save();

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
        /** @var Database $database */
        $database = $this->databaseBackup->database;

        // Send user notification of failure, etc...
        Log::error('failed to make database backup', [
            'server_id' => $database->server->id,
            'database_id' => $database->server->id,
            'ex' => $exception
        ]);

        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }
    }
}
