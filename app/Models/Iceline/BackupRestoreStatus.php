<?php


namespace Pterodactyl\Models\Iceline;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;

/**
 * @property int $id
 * @property string $type
 * @property string $error
 * @property boolean $is_successful
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property int $server_id
 * @property int $database_backup_id
 * @property int $backup_id
 *
 * @property \Pterodactyl\Models\Server $server
 * @property Backup $backup
 * @property DatabaseBackup $databaseBackup
 */
class BackupRestoreStatus extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'backup_restore_status';

    /**
     * @var array
     */
    public static $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id',
        'database_backup_id' => 'sometimes|numeric|exists:databases,id',
        'backup_id' => 'sometimes|numeric|exists:databases,id',
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
     * Get the database backup the database backup is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function databaseBackup() {
        return $this->belongsTo(DatabaseBackup::class, 'database_backup_id');
    }

    /**
     * Get the database backup the database backup is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function backup() {
        return $this->belongsTo(Backup::class, 'backup_id');
    }
}
