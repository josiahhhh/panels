<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\SubDomainCreationService;
use Pterodactyl\Services\Servers\SubDomainDeletionService;
use Pterodactyl\Services\Servers\TransferService;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class ServerTransferController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $allocationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\LocationRepository
     */
    private $locationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $nodeRepository;

    /**
     * @var \Pterodactyl\Services\Servers\TransferService
     */
    private $transferService;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonConfigurationRepository
     */
    private $daemonConfigurationRepository;

    /**
     * @var SubDomainCreationService
     */
    private $subdomainCreationService;

    /**
     * @var SubDomainDeletionService
     */
    private $subdomainDeletionService;

    /**
     * ServerTransferController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $allocationRepository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Pterodactyl\Repositories\Eloquent\LocationRepository $locationRepository
     * @param \Pterodactyl\Repositories\Eloquent\NodeRepository $nodeRepository
     * @param \Pterodactyl\Services\Servers\TransferService $transferService
     * @param \Pterodactyl\Repositories\Wings\DaemonConfigurationRepository $daemonConfigurationRepository
     * @param SubDomainCreationService $subdomainCreationService
     * @param SubDomainDeletionService $subdomainDeletionService
     */
    public function __construct(
        AlertsMessageBag $alert,
        AllocationRepositoryInterface $allocationRepository,
        ServerRepository $repository,
        LocationRepository $locationRepository,
        NodeRepository $nodeRepository,
        TransferService $transferService,
        DaemonConfigurationRepository $daemonConfigurationRepository,
        SubDomainCreationService $subdomainCreationService,
        SubDomainDeletionService $subdomainDeletionService
    ) {
        $this->alert = $alert;
        $this->allocationRepository = $allocationRepository;
        $this->repository = $repository;
        $this->locationRepository = $locationRepository;
        $this->nodeRepository = $nodeRepository;
        $this->transferService = $transferService;
        $this->daemonConfigurationRepository = $daemonConfigurationRepository;
        $this->subdomainCreationService = $subdomainCreationService;
        $this->subdomainDeletionService = $subdomainDeletionService;
    }

    /**
     * Starts a transfer of a server to a new node.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function transfer(Request $request, Server $server)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_servers != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}
        $validatedData = $request->validate([
            'node_id' => 'required|exists:nodes,id',
            'allocation_id' => 'required|bail|unique:servers|exists:allocations,id',
            'allocation_additional' => 'nullable',
        ]);

        $node_id = $validatedData['node_id'];
        $allocation_id = intval($validatedData['allocation_id']);
        $additional_allocations = array_map('intval', $validatedData['allocation_additional'] ?? []);

        // Check if the node is viable for the transfer.
        $node = $this->nodeRepository->getNodeWithResourceUsage($node_id);
        if ($node->isViable($server->memory, $server->disk)) {
            // Check if the selected daemon is online.
            $this->daemonConfigurationRepository->setNode($node)->getSystemInformation();

            $server->validateTransferState();

            // Create a new ServerTransfer entry.
            $transfer = new ServerTransfer();

            $transfer->server_id = $server->id;
            $transfer->old_node = $server->node_id;
            $transfer->new_node = $node_id;
            $transfer->old_allocation = $server->allocation_id;
            $transfer->new_allocation = $allocation_id;
            $transfer->old_additional_allocations = $server->allocations->where('id', '!=', $server->allocation_id)->pluck('id');
            $transfer->new_additional_allocations = $additional_allocations;

            $transfer->save();

            // Add the allocations to the server so they cannot be automatically assigned while the transfer is in progress.
            $this->assignAllocationsToServer($server, $node_id, $allocation_id, $additional_allocations);

            { // Re-create the server subdomain with the new allocation id
                { // Delete any subdomains
                    $this->subdomainDeletionService->delete($server->id, $server->egg_id);
                }

                { // Auto generate a subdomain for the new primary allocation
                    $domainName = 'iceline.host';
                    if ($server->egg->nest->name === 'FiveM') {
                        $domainName = 'fivem.host';
                    }

                    $subdomain = sprintf('%s', $server->uuidShort);

                    $domains = DB::table('subdomain_manager_domains')
                        ->where('domain', '=', $domainName)->get();
                    if (count($domains) < 1) {
                        Log::error('Failed to find domain for domain auto creation', [
                            'domain'
                        ]);
                    }

                    $domain = $domains[0];

                    // Append the server id to the subdomain if it already exists
                    $subdomainIsset = DB::table('subdomain_manager_subdomains')->where('domain_id', '=', $domain->id)->where('subdomain', '=', $subdomain)->get();
                    if (count($subdomainIsset) > 0) {
                        Log::info('found existing subdomain', [
                            'subdomain' => $subdomain,
                            'domain_id' => $domain->id,
                            'subdomainIsset' => $subdomainIsset
                        ]);

                        $subdomain = $subdomain . $server->uuidShort;
                    }

                    try {
                        $allocation = $node->allocations()->where('id', '=', $allocation_id)->get();
                        if ($allocation->count() <= 0) {
                            throw new DisplayException("Failed to retrieve primary allocation");
                        }

                        $this->subdomainCreationService->create($subdomain, $domain->id, $server, $allocation);

                        Log::info('recreated subdomain on transfer', [
                            'subdomain' => $subdomain,
                            'domain_id' => $domain->id,
                            'server_id' => $server->id,
                            'allocation' => $allocation
                        ]);
                    } catch (Exception $ex) {
                        Log::error('Failed to auto-create subdomain for server', [
                            'server_id' => $server->id,
                            'ex' => $ex
                        ]);
                    }
                }
            }

            // Request an archive from the server's current daemon. (this also checks if the daemon is online)
            $this->transferService->requestArchive($server);

            $this->alert->success(trans('admin/server.alerts.transfer_started'))->flash();
        } else {
            $this->alert->danger(trans('admin/server.alerts.transfer_not_viable'))->flash();
        }

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Assigns the specified allocations to the specified server.
     */
    private function assignAllocationsToServer(Server $server, int $node_id, int $allocation_id, array $additional_allocations)
    {
        $allocations = $additional_allocations;
        array_push($allocations, $allocation_id);

        $unassigned = $this->allocationRepository->getUnassignedAllocationIds($node_id);

        $updateIds = [];
        foreach ($allocations as $allocation) {
            if (!in_array($allocation, $unassigned)) {
                continue;
            }

            $updateIds[] = $allocation;
        }

        if (!empty($updateIds)) {
            $this->allocationRepository->updateWhereIn('id', $updateIds, ['server_id' => $server->id]);
        }
    }
}
