<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Iceline\BackupRestoreStatus;
use Pterodactyl\Models\Iceline\BackupSettings;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class BackupSettingsController extends ClientApiController {
    /**
     * BackupController constructor.
     *
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Returns the current backup settings for the server.
     *
     * @param Server $server
     *
     * @return JsonResponse
     */
    public function index (Request $request, Server $server) {
        /** @var BackupSettings $status */
        $settings = BackupSettings::where('server_id', '=', $server->id)->first();

        if ($settings == null) {
            $settings = new BackupSettings();
            $settings->server_id = $server->id;
            $settings->backup_retention = 0;
            $settings->save();

            return response()->json($settings, 200);
        }

        return response()->json($settings, 200);
    }

    /**
     * Updates the backup settings for the server.
     *
     * @param Server $server
     *
     * @return JsonResponse
     */
    public function update (Request $request, Server $server) {
        $validatedData = $request->validate([
            'backup_retention' => 'required|integer|min:0',
        ]);

        // Get a handle to the backup settings
        $settings = BackupSettings::where('server_id', '=', $server->id)->first();
        if ($settings == null) {
            $settings = new BackupSettings();
            $settings->server_id = $server->id;
        }

        $settings->backup_retention = $validatedData['backup_retention'];

        $settings->save();

        return response()->json($settings, 200);
    }
}
