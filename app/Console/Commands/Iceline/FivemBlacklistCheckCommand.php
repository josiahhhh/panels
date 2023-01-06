<?php

namespace Pterodactyl\Console\Commands\Iceline;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Iceline\BackupSettings;
use Pterodactyl\Models\Iceline\IPAddress;
use Pterodactyl\Models\Iceline\ReserveIPAddress;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Backups\DeleteBackupService;
use Pterodactyl\Services\Iceline\IPAddressService;
use Pterodactyl\Services\Iceline\IPCheckService;
use SplFileInfo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class FivemBlacklistCheckCommand extends Command
{

    /**
     * @var string
     */
    protected $description = 'Checks for blacklisted fivem ip addresses and adds them to a table';

    /**
     * @var IPCheckService
     */
    private $ipCheckService;

    /**
     * @var IPAddressService
     */
    private $ipAddressService;

    /**
     * @var string
     */
    protected $signature = 'p:iceline:fivem-blacklist-check';

    /**
     * FivemBlacklistCheckCommand constructor.
     *
     * @param IPCheckService $ipCheckService
     * @param IPAddressService $ipAddressService
     */
    public function __construct(
        IPCheckService $ipCheckService,
        IPAddressService $ipAddressService
    ) {
        parent::__construct();

        $this->ipCheckService = $ipCheckService;
        $this->ipAddressService = $ipAddressService;
    }

    /**
     * Handle command execution.
     */
    public function handle() {
        Log::info('starting fivem blacklist check');

        // Retrieve any blacklisted allocations
        $blacklistedAllocationIPs = $this->ipCheckService->handle(301);

        // Process the blacklisted IPs
        /** @var Allocation $allocation */
        foreach ($blacklistedAllocationIPs as $allocation) {
            Log::info('attempting to change blacklisted IP address', [
                'ip_address' => $allocation->ip
            ]);

            // Check for available reserve IPs
            $reserve = ReserveIPAddress::where('node_id', $allocation->node_id)->first();
            if (is_null($reserve)) {
                Log::error('no available reserve addresses for node', [
                    'node_id' => $allocation->node_id
                ]);
                continue;
            }

            // Change the IP address
            $this->ipAddressService->change($allocation->ip, $reserve->ip_address, false, true, 'FiveM Ban: ');

            Log::info('changed blacklisted IP address', [
                'ip_address' => $allocation->ip
            ]);
        }

        Log::info('finished checking fivem blacklisted ips');
    }
}
