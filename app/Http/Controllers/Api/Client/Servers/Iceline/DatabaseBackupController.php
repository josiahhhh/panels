<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Iceline\DatabaseBackupManager;
use Pterodactyl\Http\Requests\Api\Client\Servers\DatabaseBackups\DeleteDatabaseBackupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\DatabaseBackups\DownloadDatabaseBackupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\DatabaseBackups\GetDatabaseBackupsRequest;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Iceline\DatabaseBackup;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\DatabaseBackups\StoreDatabaseBackupRequest;
use Pterodactyl\Transformers\Api\Client\Iceline\DatabaseBackupTransformer;

class DatabaseBackupController extends ClientApiController {

    /**
     * @var DatabaseBackupManager
     */
    private $databaseBackupManager;

    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface
     */
    private $hashids;

    /**
     * BackupController constructor.
     *
     * @param DatabaseBackupManager $databaseBackupManager
     * @param HashidsInterface $hashids
     */
    public function __construct(
        DatabaseBackupManager $databaseBackupManager,
        HashidsInterface $hashids
    ) {
        parent::__construct();

        $this->databaseBackupManager = $databaseBackupManager;
        $this->hashids = $hashids;
    }

    /**
     * Returns a list of database backups for the server.
     *
     * @param GetDatabaseBackupsRequest $request
     * @param Server $server
     *
     * @return array
     */
    public function index (GetDatabaseBackupsRequest $request, Server $server) {
        return $this->fractal->collection(DatabaseBackup::where('server_id', '=', $server->id)->orderBy('created_at', 'DESC')->paginate(20))
            ->transformWith($this->getTransformer(DatabaseBackupTransformer::class))
            ->toArray();
    }

    /**
     * Creates a new database backup.
     *
     * @param StoreDatabaseBackupRequest $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     */
    public function create (StoreDatabaseBackupRequest $request, Server $server) {
        // Get the request parameters
        $name = $request->input('name'); // the name of the backup
        $database_id_hash = $request->input('database'); // a hash of the id of the database to start a backup for
        $database_id = $this->hashids->decodeFirst($database_id_hash);

        // Get a handle to the database to backup
        /** @var Database $database */
        $database = $server->databases()->where('id', '=', $database_id)->first();
        if ($database == null) {
            throw new DisplayException('Cannot find database with id ' . $database_id);
        }

        // Start the database backup process
        $databaseBackup = $this->databaseBackupManager->start($server, $database, $name);

        return $this->fractal->item($databaseBackup)
            ->transformWith($this->getTransformer(DatabaseBackupTransformer::class))
            ->toArray();
    }

    /**
     * Delete a database backup.
     * @param DeleteDatabaseBackupRequest $request
     * @param Server $server
     * @param $id
     * @return array
     * @throws DisplayException
     */
    public function delete (DeleteDatabaseBackupRequest $request, Server $server, $id) {
        $backup = DatabaseBackup::where('uuid' ,'=', (string)$id)->first();
        if ($backup == null) {
            throw new DisplayException('Cannot find database backup with uuid ' . $id);
        }

        if ($backup->server_id != $server->id) {
            throw new DisplayException('Backup id is not for the specified server');
        }

        // Delete the backup
        $this->databaseBackupManager->delete($backup->uuid);

        return [
            'success' => true,
            'data' => $backup
        ];
    }

    /**
     * Delete a database backup.
     * @param DownloadDatabaseBackupRequest $request
     * @param Server $server
     * @param $id
     * @return JsonResponse
     * @throws DisplayException
     * @throws \obregonco\B2\Exceptions\CacheException
     */
    public function download (DownloadDatabaseBackupRequest $request, Server $server, $id) {
        /** @var DatabaseBackup $backup */
        $backup = DatabaseBackup::where('uuid' ,'=', (string)$id)->first();
        if ($backup == null) {
            throw new DisplayException('Cannot find database backup with uuid ' . $id);
        }

        if ($backup->server_id != $server->id) {
            throw new DisplayException('Backup id is not for the specified server');
        }

        // Get the download URL for the backup
        $url = $this->databaseBackupManager->getDownloadURL($backup->uuid);

        return new JsonResponse([
            'url' => $url
        ]);
    }

    /**
     * Restores a database backup.
     *
     * @param DownloadDatabaseBackupRequest $request
     * @param Server $server
     * @param $id
     * @return JsonResponse
     */
    public function restore (DownloadDatabaseBackupRequest $request, Server $server, $id) {
        /** @var DatabaseBackup $backup */
        $backup = DatabaseBackup::where('uuid' ,'=', (string)$id)->first();
        if ($backup == null) {
            throw new DisplayException('Cannot find database backup with uuid ' . $id);
        }

        if ($backup->server_id != $server->id) {
            throw new DisplayException('Backup id is not for the specified server');
        }

        $this->databaseBackupManager->restore($backup->uuid);

        return new JsonResponse($backup);
    }
}
