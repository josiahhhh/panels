<?php

namespace Pterodactyl\Transformers\Api\Client\Iceline;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Iceline\DatabaseBackup;
use Pterodactyl\Models\Location;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

class LocationTransformer extends BaseClientTransformer {
    /**
     * @return string
     */
    public function getResourceName(): string {
        return "location";
    }

    /**
     * @param Location $location
     * @return array
     */
    public function transform(Location $location) {
        return [
            'id' => $location->id,
            'long' => $location->long,
            'short' => $location->short,
            'created_at' => $location->created_at->toIso8601String()
        ];
    }
}
