<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\DatabaseBackups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DownloadDatabaseBackupRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_DATABASE_BACKUP_DOWNLOAD;
    }

}
