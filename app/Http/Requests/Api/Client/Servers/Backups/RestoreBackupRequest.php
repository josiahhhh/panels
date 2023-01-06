<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RestoreBackupRequest extends ClientApiRequest {
    /**
     * @return string
     */
    public function permission() {
        return 'backup.restore';
    }

    /**
     * Ensure that this backup belongs to the server that is also present in the
     * request.
     *
     * @return bool
     */
    public function resourceExists(): bool {
        /** @var \Pterodactyl\Models\Server|mixed $server */
        $server = $this->route()->parameter('server');
        /** @var \Pterodactyl\Models\Backup|mixed $backup */
        $backup = $this->route()->parameter('backup');

        if ($server instanceof Server && $backup instanceof Backup) {
            if ($server->exists && $backup->exists && $server->id === $backup->server_id) {
                return true;
            }
        }

        return false;
    }
}
