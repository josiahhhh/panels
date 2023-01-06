<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\DatabaseBackups;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreDatabaseBackupRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_DATABASE_BACKUP_CREATE;
    }
}
