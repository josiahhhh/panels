<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Iceline\BackupRestoreStatus;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class BackupRestoreStatusController extends ClientApiController {
    /**
     * BackupController constructor.
     *
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Returns a currently running backup restoration.
     *
     * @param Request $request
     * @param Server $server
     *
     * @return JsonResponse
     * @throws DisplayException
     */
    public function index (Request $request, Server $server) {
        /** @var BackupRestoreStatus $status */
        $status = BackupRestoreStatus::where('server_id', '=', $server->id)
            ->whereNull('completed_at')->first();

        if ($status == null) {
            return response()->json([], 404);
        }

        return response()->json($status, 200);
    }
}
