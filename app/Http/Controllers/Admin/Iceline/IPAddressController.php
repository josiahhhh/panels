<?php

namespace Pterodactyl\Http\Controllers\Admin\Iceline;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException;
use Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;
use Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Iceline\IPAddress;
use Pterodactyl\Models\Iceline\IPAddressLog;
use Pterodactyl\Models\Iceline\ReserveIPAddress;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Allocations\SetDefaultAllocationService;
use Pterodactyl\Services\Servers\BuildModificationService;
use Spatie\QueryBuilder\QueryBuilder;

class IPAddressController extends Controller {

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
     * IPAddressController constructor.
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
     * Returns an overview of panel IP addresses.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_addresses != 1 && $role->p_addresses != 2) {return abort(403);}}}

        // Get all IP addresses used by allocations
        //$addresses = QueryBuilder::for(Allocation::query())->paginate(25);
        $assignedAddresses = Allocation::query()
            ->where('ip', '!=', '0.0.0.0')
            ->where('ip', '!=', '127.0.0.1')
            ->groupby('ip')
            ->distinct('ip')
            ->get();
        $fivemAddresses = Allocation::query()->where('port', 'LIKE', '301%')->groupby('ip')->distinct('ip')->get();

        // Loop over allocations and check
        // if they've been blacklisted.
        /** @var Allocation $allocation */
        foreach ($assignedAddresses as $allocation) {
            $hasFivemPorts = false;

            /** @var Allocation $allocation */
            foreach ($fivemAddresses as $fivemAllocation) {
                if ($fivemAllocation->id == $allocation->id) {
                    $hasFivemPorts = true;
                    break;
                }
            }

            if ($hasFivemPorts == false) {
                $allocation->fivem_blacklisted = null;
                continue;
            }

            $allocation->fivem = true;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://runtime.fivem.net/blacklist/' . $allocation->ip);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_exec($curl);

            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Check the return status
            if ($http_status == 200) {
                $allocation->fivem_blacklisted = true;
            } else {
                $allocation->fivem_blacklisted = false;
            }
        }

        return view('admin.addresses.index', [
            'assigned' => $assignedAddresses,
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|View
     */
    public function changeIndex(Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_addresses != 1 && $role->p_addresses != 2) {return abort(403);}}}

        $validated = $this->validate($request, [
            'node' => 'sometimes|exists:nodes,id'
        ]);

        $nodes = DB::table('nodes')->get();
        $allocations = DB::table('allocations')->orderBy('port', 'ASC')->get();

        $assignedAddresses = null;
        $reservedAddresses = null;

        $node = null;
        if(array_key_exists('node', $validated)) {
            $node = Node::find($validated['node']);

            $assignedAddresses = Allocation::query()->where('node_id', $node->id)->groupby('ip')->distinct('ip')->get();
            $reservedAddresses = ReserveIPAddress::where('node_id', $node->id)->get();
        } else {
            $assignedAddresses = Allocation::query()->groupby('ip')->distinct('ip')->get();
            $reservedAddresses = ReserveIPAddress::all();
        }

        return view('admin.addresses.change', [
            'node' => $node,

            'nodes' => $nodes,
            'allocations' => $allocations,

            'assigned' => $assignedAddresses,
            'reserved' => $reservedAddresses,

            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'alias' => $request->query('alias')
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws CidrOutOfRangeException
     * @throws DisplayException
     * @throws InvalidPortMappingException
     * @throws PortOutOfRangeException
     * @throws ServerUsingAllocationException
     * @throws TooManyPortsInRangeException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\AllocationDoesNotBelongToServerException
     */
    public function global(Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_addresses != 2) {return abort(403);}}}

        $this->validate($request, [
            'old_ip' => 'required|ip',
            'new_ip' => 'required|ip'
        ]);

        $old_ip = $request->input('old_ip', 0);
        $new_ip = $request->input('new_ip', 0);

        $reserve = ReserveIPAddress::where('ip_address', $new_ip)->first();

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

            $alias = $allocation->ip_alias;
            if (!is_null($reserve) && !is_null($reserve->alias)) {
                $alias = $reserve->alias;
            }

            $this->assignmentService->handle($node, [
                'allocation_ip' => $new_ip,
                'allocation_alias' => $alias,
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
        $log->reason = 'Changed ' . $old_ip . ' to ' . $new_ip;
        $log->save();

        if ($request->has('add_to_reserve')) {
            $address = new ReserveIPAddress();
            $address->ip_address = $old_ip;
            $address->save();

            Log::info('added changed ip address to reserve', [
                'old_ip' => $old_ip
            ]);

            $log = new IPAddressLog();
            $log->ip_address = $old_ip;
            $log->node_id = $allocations[0]->node_id;
            $log->reason = 'Added ' . $old_ip . ' to reserve after replacing it with' . $new_ip;
            $log->save();
        }

        $remove_from_reserve = $request->input('remove_from_reserve', true);
        if (!is_null($reserve) && $request->has('remove_from_reserve')) {
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
                $log->reason = 'Removed ' . $new_ip . ' from reserve after replacing ' . $old_ip;
                $log->save();
            }
        }

        return redirect()->route('admin.addresses.changeIndex');
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

    /**
     * Returns a list of reserved ip addresses.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reserve() {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_addresses != 1 && $role->p_addresses != 2) {return abort(403);}}}

        // Get all IP addresses used by allocations
        //$addresses = QueryBuilder::for(Allocation::query())->paginate(25);
        // $assignedAddresses = Allocation::query()->groupby('ip')->distinct('ip')->get();

        $addresses = ReserveIPAddress::all();

        // Loop over reserve ips and check
        // if they've been blacklisted.
        foreach ($addresses as $address) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://runtime.fivem.net/blacklist/' . $address->ip_address);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_exec($curl);

            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            $blacklisted = false;

            // Check the return status
            if ($http_status == 200) {
                $blacklisted = true;
            }

            $address->fivem_blacklisted = $blacklisted;
        }

        $nodes = Node::all();
        $locations = Location::all();

        return view('admin.addresses.reserve', [
            'addresses' => $addresses,
            'nodes' => $nodes,
            'locations' => $locations
        ]);
    }

    /**
     * Adds reserve ip addresses.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addReserve (Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_addresses != 2) {return abort(403);}}}

        $validated = $this->validate($request, [
            'ip_address' => 'required|ip',
            'node_id' => 'required|exists:nodes,id',
            'alias' => 'sometimes'
        ]);

        // Save the new address
        $address = new ReserveIPAddress();
        $address->ip_address = $validated['ip_address'];
        $address->node_id = $validated['node_id'];
        $address->alias = $validated['alias'];
        $address->save();

        // Return to the reserve address list
        return redirect()->route('admin.addresses.reserve');
    }

    /**
     * Removes a reserved ip addresses.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function removeReserve (Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_addresses != 2) {return abort(403);}}}

        $validated = $this->validate($request, [
            'ip_address' => 'required|ip',
        ]);

        // Save the new address
        $address = ReserveIPAddress::query()->where('ip_address', $validated['ip_address']);
        $address->delete();

        // Return to the reserve address list
        return redirect()->route('admin.addresses.reserve');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|View
     */
    public function logs(Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_addresses != 1 && $role->p_addresses != 2) {return abort(403);}}}

        $logs = IPAddressLog::query()->orderBy('created_at', 'desc')->get();

        return view('admin.addresses.logs', [
            'logs' => $logs
        ]);
    }
}
