<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Pterodactyl\Models\Backup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Mhetreramesh\Flysystem\BackblazeAdapter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class B2BackupRemoteUploadController extends Controller
{
    const PART_SIZE = 5 * 1024 * 1024 * 1024;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * BackupRemoteUploadController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Pterodactyl\Extensions\Backups\BackupManager $backupManager
     */
    public function __construct(BackupRepository $repository, BackupManager $backupManager)
    {
        $this->repository = $repository;
        $this->backupManager = $backupManager;
    }

    /**
     * Returns the required presigned urls to upload a backup to S3 cloud storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $backup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function index(Request $request, string $backup)
    {
        // Get the size query parameter.
        $size = (int) $request->query('size');
        if (empty($size)) {
            throw new BadRequestHttpException('A non-empty "size" query parameter must be provided.');
        }

        /** @var \Pterodactyl\Models\Backup $backup */
        $backup = Backup::query()->where('uuid', $backup)->firstOrFail();

        // Prevent backups that have already been completed from trying to
        // be uploaded again.
        if (!is_null($backup->completed_at)) {
            return new JsonResponse([], JsonResponse::HTTP_CONFLICT);
        }

        // Ensure we are using the B2 adapter.
        $adapter = $this->backupManager->adapter();
        if (!$adapter instanceof BackblazeAdapter) {
            throw new BadRequestHttpException('The configured backup adapter is not an B2 compatible adapter.');
        }

        // Acquire the auth token
        $client = new Client();
        $res = $client->request('GET', 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account', [
            'headers' => [
                'Accept' => 'application/json',
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
            'capabilities' => ['listBuckets', 'writeFiles'],
            'validDurationInSeconds' => 2 * 60 * 60, // 2 hours
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

        return new JsonResponse([
            'application_key_id' => $keyData['applicationKeyId'],
            'application_key' => $keyData['applicationKey'],
            'bucket_id' => $keyData['bucketId'],
            'bucket_name' => config('backups.disks.b2.bucket_name'),
            'path' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid)
        ]);
    }
}
