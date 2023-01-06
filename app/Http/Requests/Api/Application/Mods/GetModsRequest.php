<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mods;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetModsRequest extends ApplicationApiRequest {
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_MODS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
