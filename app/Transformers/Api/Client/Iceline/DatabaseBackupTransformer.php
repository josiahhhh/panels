<?php

namespace Pterodactyl\Transformers\Api\Client\Iceline;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Iceline\DatabaseBackup;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

class DatabaseBackupTransformer extends BaseClientTransformer {
    /**
     * @return string
     */
    public function getResourceName(): string {
        return "database_backup";
    }

    /**
     * @param DatabaseBackup $backup
     * @return array
     */
    public function transform(DatabaseBackup $backup) {
        return [
            'uuid' => $backup->uuid,
            'server_id' => $backup->server_id,
            'database_id' => $backup->database_id,
            'is_successful' => $backup->is_successful,
            'name' => $backup->name,
            'created_at' => $backup->created_at->toIso8601String(),
            'completed_at' => $backup->completed_at ? $backup->completed_at->toIso8601String() : null,
            'error' => $backup->error,
            'bytes' => $backup->bytes,
        ];
    }
}
