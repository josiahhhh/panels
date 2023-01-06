<?php

namespace Pterodactyl\Extensions\Iceline;

use Illuminate\Support\Str;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Models\Iceline\DatabaseBackup;
use Pterodactyl\Jobs\Iceline\RestoreDatabaseBackup;
use Pterodactyl\Jobs\Iceline\InitiateDatabaseBackup;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Pterodactyl\Exceptions\Service\Backup\BackupQuotaReachedException;

class DatabaseBackupManager
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * BackupManager constructor.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->config = $app->make(Repository::class);
    }

    /**
     * Starts a database backup for the specified server and database.
     * @param Server $server
     * @param Database $database
     * @param string $name
     *
     * @return DatabaseBackup
     * @throws TooManyBackupsException
     */
    public function start(Server $server, Database $database, string $name): DatabaseBackup
    {
        if ($server->backup_limit) {
            // if ($server->backup_limit_by_size) {
            $fileBackupTotalGB = DB::table('backups')
                    ->where('server_id', '=', $server->id)
                    ->where('is_successful', '=', true)
                    ->whereNull('deleted_at')
                    ->sum('bytes') / 1073741824;
            $databaseBackupTotalGB = DB::table('database_backups')
                    ->where('server_id', '=', $server->id)
                    ->where('is_successful', '=', true)
                    ->sum('bytes') / 1073741824;
            $totalUsed = $fileBackupTotalGB + $databaseBackupTotalGB;
            if ($totalUsed >= $server->backup_limit) {
                throw new BackupQuotaReachedException($totalUsed, $server->backup_limit);
            }
            //            } else {
            //                $fileBackups = DB::table('backups')
            //                    ->where('server_id', '=', $server->id)
            //                    ->where('is_successful', '=', true)
            //                    ->whereNull('deleted_at')
            //                    ->get()
            //                    ->count();
            //                $databaseBackups = DB::table('database_backups')->where('server_id', '=', $server->id)->get()->count();
            //                if (($fileBackups + $databaseBackups) >= $server->backup_limit) {
            //                    throw new TooManyBackupsException($server->backup_limit);
            //                }
            //            }
        }

        // Add the database backup to the database
        $backup = new DatabaseBackup();
        $backup->uuid = Str::orderedUuid();
        $backup->server_id = $server->id;
        $backup->database_id = $database->id;
        $backup->name = $name;
        $backup->save();

        Log::info('dispatching database backup', [
            'server_id' => $server->id,
            'database_id' => $database->id,
            'name' => $name
        ]);

        // Dispatch the database backup job
        InitiateDatabaseBackup::dispatch($backup);

        return $backup;
    }

    /**
     * Deletes a database backup.
     * @param string $uuid
     */
    public function delete(string $uuid)
    {
        $backup = DatabaseBackup::where('uuid', '=', $uuid)->first();
        if ($backup == null) {
            throw new \Exception('Can\'t find database backup with uuid ' . $uuid);
        }

        // There will only be a file to delete if the backup is successful
        if ($backup->is_successful) {
            $client = new \obregonco\B2\Client(config('backups.disks.b2.key_id'), [
                'applicationKey' => config('backups.disks.b2.application_key'),
            ]);

            $file = sprintf('%s/%s.sql',
                $backup->server->uuid,
                $backup->uuid);

            // Delete the sql backup
            $fileDelete = $client->deleteFile([
                'FileName' => $file,
                'BucketName' => config('backups.disks.b2.database_bucket_name'),
            ]);

            if (!$fileDelete) {
                $backup->error = 'Failed to delete file';
                $backup->save();

                throw new \Exception('error deleting database dump');
            }
        }

        // Delete the record
        $backup->delete();

        return $backup;
    }

    /**
     * Returns a download URL for the backup.
     *
     * @param string $uuid
     * @return string
     * @throws \obregonco\B2\Exceptions\CacheException
     */
    public function getDownloadURL(string $uuid): string
    {
        /** @var DatabaseBackup $backup */
        $backup = DatabaseBackup::where('uuid', '=', $uuid)->first();
        if ($backup == null) {
            throw new \Exception('Can\'t find database backup with uuid ' . $uuid);
        }

        // There will only be a file to download if the backup is successful
        if ($backup->is_successful) {
            $client = new \obregonco\B2\Client(config('backups.disks.b2.key_id'), [
                'applicationKey' => config('backups.disks.b2.application_key'),
            ]);

            $file = sprintf('%s/%s.sql', $backup->server->uuid, $backup->uuid);

            return $client->getDownloadUrl(
                config('backups.disks.b2.database_bucket_id'),
                $file, true, 5 * 60);
        }

        throw new \Exception('Cannot download errored database backup ' . $uuid);
    }

    /**
     * Restore the specified database backup.
     *
     * @param string $uuid
     */
    public function restore(string $uuid)
    {
        /** @var DatabaseBackup $backup */
        $backup = DatabaseBackup::where('uuid', '=', $uuid)->first();
        if ($backup == null) {
            throw new \Exception('Can\'t find database backup with uuid ' . $uuid);
        }

        // There will only be a file to download and restore if the backup is successful
        if ($backup->is_successful) {
            RestoreDatabaseBackup::dispatch($backup);

            return;
        }

        throw new \Exception('Cannot restore errored database backup ' . $uuid);
    }
}
