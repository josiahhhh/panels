<?php

namespace Pterodactyl\Services\Iceline;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Iceline\IPAddressLog;
use Pterodactyl\Models\Iceline\ReserveIPAddress;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Allocations\SetDefaultAllocationService;
use Pterodactyl\Services\Servers\BuildModificationService;

class IPAddressService {

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Allocations\SetDefaultAllocationService
     */
    protected $defaultAllocationService;

    /**
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    protected $assignmentService;

    /**
     * @var \Pterodactyl\Services\Allocations\AllocationDeletionService
     */
    protected $allocationDeletionService;

    /**
     * @var \Pterodactyl\Services\Servers\BuildModificationService
     */
    protected $buildModificationService;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonPowerRepository
     */
    protected $powerRepository;

    /**
     * @var ServerVariableRepository
     */
    private $variableRepository;

    /**
     * LocationCreationService constructor.
     *
     * @param AlertsMessageBag $alert
     * @param AssignmentService $assignmentService
     * @param AllocationDeletionService $allocationDeletionService
     * @param SetDefaultAllocationService $defaultAllocationService
     * @param BuildModificationService $buildModificationService
     * @param DaemonPowerRepository $powerRepository
     * @param ServerVariableRepository $variableRepository
     */
    public function __construct(
        AlertsMessageBag $alert,
        AssignmentService $assignmentService,
        AllocationDeletionService $allocationDeletionService,
        SetDefaultAllocationService $defaultAllocationService,
        BuildModificationService $buildModificationService,
        DaemonPowerRepository $powerRepository,
        ServerVariableRepository $variableRepository
    )
    {
        $this->alert = $alert;
        $this->assignmentService = $assignmentService;
        $this->defaultAllocationService = $defaultAllocationService;
        $this->allocationDeletionService = $allocationDeletionService;
        $this->buildModificationService = $buildModificationService;
        $this->powerRepository = $powerRepository;
        $this->variableRepository = $variableRepository;
    }

    /**
     * Change IP addresses for an allocation.
     *
     * @param string $old_ip
     * @param string $new_ip
     * @param bool $add_to_reserve
     * @param bool $remove_from_reserve
     * @return array
     *
     * @throws DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function change(string $old_ip, string $new_ip, bool $add_to_reserve = false, bool $remove_from_reserve = true, string $reason_prefix = '') {
        if ($old_ip == $new_ip) {
            throw new DisplayException('Old and new ip cannot match.');
        }

        $allocations = DB::table('allocations')->where('ip', '=', $old_ip)->get();
        if (count($allocations) < 1) {
            throw new DisplayException('There aren\'t any allocation found in this ip.');
        }

        $servers_to_start = [];

        foreach ($allocations as $allocation) {
            $node = Node::find($allocation->node_id);

            $this->assignmentService->handle($node, [
                'allocation_ip' => $new_ip,
                'allocation_alias' => $allocation->ip_alias,
                'allocation_ports' => [0 => (int) $allocation->port]
            ]);

            if (!is_null($allocation->server_id)) {
                /** @var \Pterodactyl\Models\Server $server */
                $server = Server::find($allocation->server_id); // DB::table('servers')->where('id', '=', $allocation->server_id)->first();
                $new_allocation = DB::table('allocations')->where('port', '=', $allocation->port)->where('ip', '=', $new_ip)->get();

                $this->buildModification(Server::find($server->id), [$new_allocation[0]->id], []);

                if ($server->suspended == 0) {
                    $this->powerRepository->setServer(Server::find($server->id))->send('kill');
                }

                if ($allocation->id == $server->allocation_id) {
                    $this->defaultAllocationService->handle(Server::find($server->id), $new_allocation[0]->id);
                }

                $this->buildModification(Server::find($server->id), [], [$allocation->id]);

                if ($server->suspended == 0) {
                    array_push($servers_to_start, $server->id);
                }
            }

            $this->allocationDeletionService->handle(Allocation::find($allocation->id));
        }

        foreach ($servers_to_start as $item) {
            $this->powerRepository->setServer(Server::find($item))->send('start');
        }

        $this->alert->success('You have successfully changed the IPs.')->flash();

        $log = new IPAddressLog();
        $log->ip_address = $old_ip;
        $log->to = $new_ip;
        $log->node_id = $allocations[0]->node_id;
        $log->reason = $reason_prefix . 'Changed ' . $old_ip . ' to ' . $new_ip;
        $log->save();

        if ($add_to_reserve) {
            $address = new ReserveIPAddress();
            $address->ip_address = $old_ip;
            $address->save();

            Log::info('added changed ip address to reserve', [
                'old_ip' => $old_ip
            ]);

            $log = new IPAddressLog();
            $log->ip_address = $old_ip;
            $log->node_id = $allocations[0]->node_id;
            $log->reason = $reason_prefix . 'Added ' . $old_ip . ' to reserve after replacing it with' . $new_ip;
            $log->save();
        }

        if ($remove_from_reserve) {
            /** @var ReserveIPAddress $address */
            $address = ReserveIPAddress::where('ip_address', $new_ip)->first();
            if ($address) {
                $address->delete();

                Log::info('removed used ip address in change from reserve', [
                    'new_ip' => $new_ip
                ]);

                $log = new IPAddressLog();
                $log->ip_address = $new_ip;
                $log->node_id = $allocations[0]->node_id;
                $log->reason = $reason_prefix . 'Removed ' . $new_ip . ' from reserve after replacing ' . $old_ip;
                $log->save();
            }
        }
    }

    /**
     * @param $server
     * @param array $add
     * @param array $remove
     * @return \Pterodactyl\Models\Server
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    private function buildModification($server, $add = [], $remove = [])
    {
        return $this->buildModificationService->handle($server, [
            'allocation_id' => $server->allocation_id,
            'add_allocations' => $add,
            'remove_allocations' => $remove,
            'memory' => $server->memory,
            'swap' => $server->swap,
            'io' => $server->io,
            'cpu' => $server->cpu,
            'disk' => $server->disk,
            'database_limit' => $server->database_limit,
            'allocation_limit' => $server->allocation_limit,
            'oom_disabled' => $server->oom_disabled,
            'backup_limit' => $server->backup_limit
        ]);
    }
}
