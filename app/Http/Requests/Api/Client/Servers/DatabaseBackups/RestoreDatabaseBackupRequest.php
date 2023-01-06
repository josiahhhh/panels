<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\DatabaseBackups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RestoreDatabaseBackupRequest extends ClientApiRequest {
    /**
     * @return string
     */
    public function permission() {
        return 'database_backup.restore';
    }
}
