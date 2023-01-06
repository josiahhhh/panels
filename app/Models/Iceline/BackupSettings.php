<?php


namespace Pterodactyl\Models\Iceline;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;

/**
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property int $server_id
 * @property int $backup_retention
 *
 * @property \Pterodactyl\Models\Server $server
 */
class BackupSettings extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'backup_settings';

    /**
     * @var array
     */
    public static $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id'
    ];

    /**
     * Get the server the database backup is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server() {
        return $this->belongsTo(Server::class, 'server_id');
    }
}
