<?php


namespace Pterodactyl\Models\Iceline;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;

/**
 * @property int $id
 * @property int $server_id
 *
 * @property string $key
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property boolean $blacklisted
 * @property string $cfx_url
 *
 * @property \Pterodactyl\Models\Server $server
 *
 */
class FiveMLicense extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fivem_licenses';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'server_id',
        'key',
        'blacklisted'
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'server_id' => 'integer'
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id'
    ];

    /**
     * Gets the server associated with a license.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
