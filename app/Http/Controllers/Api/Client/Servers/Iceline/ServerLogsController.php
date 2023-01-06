<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Requests\Api\Client\Servers\Logs\DeleteServerLogsRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Spatie\Activitylog\ActivitylogServiceProvider;

class ServerLogsController extends ClientApiController {

    /**
     * BackupController constructor.
     *
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Delete the logs for the server.
     *
     * @param Request $request
     * @param Server $server
     * @return array
     */
    public function delete(DeleteServerLogsRequest $request, Server $server) {
        $activity = ActivitylogServiceProvider::getActivityModelInstance();

        DB::table('activity_log')
            ->where('properties', 'like', "%\"serverID\":$server->id%")
            ->delete();

        return [
            'success' => true
        ];
    }

}
