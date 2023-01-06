<?php


namespace Pterodactyl\Models\Iceline;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

/**
 * @property int $id
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property string $ip_address
 * @property boolean $fivem_blacklisted
 *
 * @property int $node_id
 *
 * @property \Pterodactyl\Models\Node $node
 *
 */
class IPAddress extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ip_addresses';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip_address',
        'node_id',
        'fivem_blacklisted'
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'ip_address' => 'required',
        'node_id' => 'nullable|exists:nodes,id',
    ];

    /**
     * Gets the node that the IP address is assigned to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
