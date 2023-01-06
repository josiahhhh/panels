<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ModsRequest extends ClientApiRequest {
    /**
     * @return string
     */
    public function permission() {
        return 'mods.manage';
    }
}
