<?php


namespace Pterodactyl\Models\Iceline;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $error
 * @property boolean $is_successful;
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property int $server_id
 * @property int $database_id
 *
 * @property int $bytes
 *
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\Database $database
 */
class DatabaseBackup extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'database_backups';

    /**
     * @var array
     */
    public static $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id',
        'database_id' => 'required|numeric|exists:databases,id',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'completed_at',
    ];

    /**
     * Get the server the database backup is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server() {
        return $this->belongsTo(Server::class, 'server_id');
    }

    /**
     * Get the database the backup is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function database() {
        return $this->belongsTo(Database::class, 'database_id');
    }
}
