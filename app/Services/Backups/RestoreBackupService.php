<?php

namespace Pterodactyl\Services\Backups;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Iceline\BackupRestoreStatus;
use Pterodactyl\Repositories\Wings\DaemonRepository;
use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RestoreBackupService {

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonBackupRepository
     */
    private $daemonBackupRepository;

    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * InitiateBackupService constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonBackupRepository $daemonBackupRepository
     * @param \Pterodactyl\Extensions\Backups\BackupManager $backupManager
     */
    public function __construct(
        BackupRepository $repository,
        ConnectionInterface $connection,
        DaemonBackupRepository $daemonBackupRepository,
        BackupManager $backupManager
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->backupManager = $backupManager;
    }

    /**
     * Initiates the backup process for a server on the daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param Backup $backup
     * @throws Exception
     */
    public function handle(Server $server, Backup $backup) {
        if ($backup->disk === Backup::ADAPTER_BACKBLAZE_B2) {
            $this->restoreFromB2($server, $backup);

            return;
        }

        // TODO: add restoring flag to backup to prevent deleting, etc
    }

    /**
     * Stores a server backup from B2 storage.
     *
     * @param Server $server
     * @param Backup $backup
     * @throws Exception
     */
    protected function restoreFromB2 (Server $server, Backup $backup) {
        if (!$backup->is_successful) {
            throw new Exception('cannot restore backup that isn\'t finished');
        }

        // Acquire the auth token
        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push(\Sentry\Tracing\GuzzleTracingMiddleware::trace());
        $client = new Client([
            'handler'  => $stack
        ]);
        $res = $client->request('GET', 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account', [
            'headers' => [
                'Accept' => 'application/json'
            ],
            'auth' => [
                config('backups.disks.b2.key_id'),
                config('backups.disks.b2.application_key'),
            ],
        ]);
        if ($res->getStatusCode() != 200) {
            throw new Exception('Unexpected status code ' . $res->getStatusCode());
        }
        $tokenReqBody = json_decode($res->getBody(), true);
        $authToken = $tokenReqBody['authorizationToken'];
        $apiUrl = $tokenReqBody['apiUrl'];

        $keyData = array(
            'accountId' => config('backups.disks.b2.key_id'),
            'keyName' => $backup->uuid,
            'capabilities' => ['listBuckets', 'readFiles'],
            'validDurationInSeconds' => 30*60, // 30 minutes
            'bucketId' => config('backups.disks.b2.bucket_id'),
            'namePrefix' => $backup->server->uuid . '/' . $backup->uuid
        );
        $keyBody = json_encode($keyData);
        Log::info($keyBody);

        // Request a limited application key for the upload
        $res = $client->request('POST', $apiUrl . '/b2api/v2/b2_create_key', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $authToken
            ],
            'body' => $keyBody
        ]);

        // Decode the json key data
        $keyData = json_decode($res->getBody(), true);

        // Add a backup status record for the restoration
        $status = new BackupRestoreStatus();
        $status->server_id = $server->id;
        $status->type = 'file';
        $status->backup_id = $backup->id;
        $status->save();

        try {
            // Send the restore api request to the Wings daemon
            $response = $this->daemonBackupRepository
                ->setServer($server)
                ->getHttpClient()
                ->post(sprintf('/api/servers/%s/backup/%s/restore', $server->uuid, $backup->uuid), [
                    'json' => [
                        // NOTE: Ideally we'd use the adapter field from the backup object field
                        //  but from testing for some reason that is sometimes returned as blank.
                        'adapter' => $backup->adapter ?? config('backups.default'),
                        'uuid' => $backup->uuid,
                        'backup' => $backup,
                        'status_id' => $status->id,
                        'metadata' => [
                            'application_key_id' => $keyData['applicationKeyId'],
                            'application_key' => $keyData['applicationKey'],
                            'bucket_id' => $keyData['bucketId'],
                            'bucket_name' => config('backups.disks.b2.bucket_name'),
                            'path' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid)
                        ]
                    ],
                ]);

            // Update the backup status if the result is failed
            if ($response->getStatusCode() < 200
                || $response->getStatusCode() > 299) {
                $status->error = $response->getBody()->getContents();
                $status->completed_at = Carbon::now();
                $status->save();
            }
        } catch (Exception $ex) {
            $status->error = $ex->getMessage();
            $status->completed_at = Carbon::now();
            $status->save();
        }
    }
}
