<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Iceline;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Iceline\BackupRestoreStatus;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest;

class RestoreBackupController extends Controller {
    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * BackupStatusController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     */
    public function __construct(BackupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handles updating the state of a backup restoration.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $uuid, $id) {
        Log::info('received request to mark backup restoration as completed', [
            'server_id' => $uuid,
            'backup_restore_id' => $id
        ]);

        /** @var BackupRestoreStatus $status */
        $status = BackupRestoreStatus::where('id', '=', (int)$id)->first();
        if ($status == null) {
            Log::error('received daemon attempt to mark non-existent backup restore status as restored', [
                'server_id' => $uuid,
                'backup_restore_id' => (int)$id
            ]);

            throw new Exception("cannot find backup restore status");
        }

        // Update the backup restore status
        $successful = $request->input('successful') ? true : false;
        $errorText = $request->input('error');
        $status->forceFill([
            'is_successful' => $successful,
            'completed_at' => CarbonImmutable::now(),
            'error' => $errorText,
        ])->save();

        // Delete the restore status since it's done
        $status->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
