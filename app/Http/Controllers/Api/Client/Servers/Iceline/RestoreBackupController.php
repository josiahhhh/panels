<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use Exception;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\RestoreBackupRequest;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Services\Backups\RestoreBackupService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class RestoreBackupController extends ClientApiController {
    /**
     * @var \Pterodactyl\Services\Backups\RestoreBackupService
     */
    private $restoreBackupService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * BackupController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param RestoreBackupService $restoreBackupService
     */
    public function __construct(
        BackupRepository $repository,
        RestoreBackupService $restoreBackupService
    ) {
        parent::__construct();

        $this->restoreBackupService = $restoreBackupService;
        $this->repository = $repository;
    }

    /**
     * Starts restoring a backup to a server
     *
     * @param RestoreBackupRequest $request
     * @param Server $server
     * @param Backup $backup
     * @return JsonResponse
     * @throws Exception
     */
    public function restore(RestoreBackupRequest $request, Server $server, Backup $backup) {
        $this->restoreBackupService->handle($server, $backup);

        return JsonResponse::create([], JsonResponse::HTTP_NO_CONTENT);
    }
}
