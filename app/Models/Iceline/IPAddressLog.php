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
 * @property int $node_id
 * @property string $ip_address
 * @property string $to
 * @property string $reason
 *
 * @property \Pterodactyl\Models\Node $node
 *
 */
class IPAddressLog extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ip_address_logs';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip_address',
        'to',
        'node_id',
        'reason'
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'ip_address' => 'required',
        'to' => 'nullable|ip',
        'node_id' => 'nullable|exists:nodes,id',
        'reason' => 'required'
    ];

    /**
     * Gets the node that the IP address is assigned to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node() {
        return $this->belongsTo(Node::class);
    }
}
