<?php

namespace Pterodactyl\Services\Iceline;

use Exception;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\TransferService;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Services\Servers\SubDomainCreationService;
use Pterodactyl\Services\Servers\SubDomainDeletionService;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class ServerTransferService
{

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
     * @var \Pterodactyl\Services\Servers\SuspensionService
     */
    private $suspensionService;

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
     * ServerTransferService constructor.
     *
     * @param AllocationRepositoryInterface $allocationRepository
     * @param ServerRepository $repository
     * @param LocationRepository $locationRepository
     * @param NodeRepository $nodeRepository
     * @param SuspensionService $suspensionService
     * @param TransferService $transferService
     * @param DaemonConfigurationRepository $daemonConfigurationRepository
     * @param SubDomainCreationService $subdomainCreationService
     * @param SubDomainDeletionService $subdomainDeletionService
     */
    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        ServerRepository              $repository,
        LocationRepository            $locationRepository,
        NodeRepository                $nodeRepository,
        SuspensionService             $suspensionService,
        TransferService               $transferService,
        DaemonConfigurationRepository $daemonConfigurationRepository,
        SubDomainCreationService      $subdomainCreationService,
        SubDomainDeletionService      $subdomainDeletionService
    )
    {
        $this->allocationRepository = $allocationRepository;
        $this->repository = $repository;
        $this->locationRepository = $locationRepository;
        $this->nodeRepository = $nodeRepository;
        $this->suspensionService = $suspensionService;
        $this->transferService = $transferService;
        $this->daemonConfigurationRepository = $daemonConfigurationRepository;
        $this->subdomainCreationService = $subdomainCreationService;
        $this->subdomainDeletionService = $subdomainDeletionService;
    }

    /**
     * Check IP addresses
     *
     * @param Server $server
     * @return object
     *
     * @throws \xPaw\SourceQuery\Exception\InvalidArgumentException
     * @throws \xPaw\SourceQuery\Exception\InvalidPacketException
     * @throws \xPaw\SourceQuery\Exception\SocketException
     */
    public function handle(Server $server, string $location_id)
    {
        $location = $this->locationRepository->getWithNodes($location_id);

        $portMin = 0;
        $portMax = 65535;

        switch ($server->nest->name) {
            case "Source Engine":
                $portMin = 27025;
                $portMax = 27095;
                break;
            default:
                break;
        }

        switch ($server->egg->name) {
            case "Minecraft":
                $portMin = 25505;
                $portMax = 25555;
                break;
            case "Rust":
                $portMin = 28025;
                $portMax = 28095;
                break;
            case "FiveM":
                $portMin = 30125;
                $portMax = 30195;
                break;
            default:
                break;
        }

        $requiredAllocations = $server->allocations->count();

        // Attempt to find a suitable node with free allocations
        $node = $this->findNode($server, $location, $requiredAllocations, $portMin, $portMax);
        if (is_null($node)) {
            throw new DisplayException('Failed to find a viable node with enough resources for your server in that location.');
        }

        // Get the list of notifications
        $allocations = $this->findAllocations($node, $requiredAllocations, $portMin, $portMax);
        if (count($allocations) <= 0) {
            throw new DisplayException("All available ports where reserved, try transferring again.");
        } else if (count($allocations) != $requiredAllocations) {
            Log::info("findAllocations failed to return the correct amount of allocations", [
                'count' => $allocations,
                'required' => $requiredAllocations
            ]);

            throw new DisplayException("Failed to allocation enough ports, try transferring again.");
        } else {
            $primary_allocation_id = array_shift($allocations);
            $additional_allocations = $allocations;
        }

        // Check that an allocation was found
        if ($primary_allocation_id == null) {
            Log::info("failed to find free allocation in location", [
                'location' => $location
            ]);

            throw new DisplayException('Failed to find node in location with free allocation.');
        }

        // Check if the node is viable for the transfer.
        $node = $this->nodeRepository->getNodeWithResourceUsage($node->id);
        if ($node->isViable($server->memory, $server->disk)) {
            // Check if the selected daemon is online.
            $this->daemonConfigurationRepository->setNode($node)->getSystemInformation();

            // Create a new ServerTransfer entry.
            $transfer = new ServerTransfer;

            $transfer->server_id = $server->id;
            $transfer->old_node = $server->node_id;
            $transfer->new_node = $node->id;
            $transfer->old_allocation = $server->allocation_id;
            $transfer->new_allocation = $primary_allocation_id;
            $transfer->old_additional_allocations = $server->allocations->where('id', '!=', $server->allocation_id)->pluck('id');
            $transfer->new_additional_allocations = $additional_allocations;

            $transfer->save();

            // Add the allocations to the server so they cannot be automatically assigned while the transfer is in progress.
            $this->assignAllocationsToServer($server, $node->id, $primary_allocation_id, $additional_allocations);

            // Re-create the server subdomain with the new allocation id
            // Delete any subdomains
            $this->subdomainDeletionService->delete($server->id, $server->egg_id);

            // Auto generate a subdomain for the new primary allocation
            $domainName = 'iceline.host';
            if ($server->egg->nest->name === 'FiveM') {
                $domainName = 'fivem.host';
            }

            $subdomain = sprintf('%s-%s', $location->short, $server->uuidShort);

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
                $allocation = $node->allocations()->where('id', '=', $primary_allocation_id)->get();
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

            // Request an archive from the server's current daemon. (this also checks if the daemon is online)
            $this->transferService->requestArchive($server);
        } else {
            throw new DisplayException("The location you selected is not viable for this transfer."); // trans('admin/server.alerts.transfer_not_viable'));

            /* return response()->json([
                'success' => false,
                'data' => [],
                'error' => [
                    'message' => trans('admin/server.alerts.transfer_not_viable'),
                ]
            ], 400); */
        }

        // Log
        /* $user = $request->user();
        activity()
            ->causedBy($user)
            ->performedOn($server)
            ->withProperties([
                'serverID' => $server->id,
                'module' => 'Server Transfer',
                'type' => 'Started',
                'status' => 'Danger'
            ])
            ->log($user->name_first.' '.$user->name_last.' started transferring the server to "'.$location->long.'('.$location->short.')".'); */

        return response()->json([
            'success' => true,
            'data' => [
                'message' => trans('admin/server.alerts.transfer_started')
            ],
        ], 200);
    }

    /**
     * Check IP addresses
     *
     * @param Server $server
     * @return object
     *
     * @throws \xPaw\SourceQuery\Exception\InvalidArgumentException
     * @throws \xPaw\SourceQuery\Exception\InvalidPacketException
     * @throws \xPaw\SourceQuery\Exception\SocketException
     */
    public function handleToNode(Server $server, Node $node)
    {
        $portMin = 0;
        $portMax = 65535;

        switch ($server->nest->name) {
            case "Source Engine":
                $portMin = 27025;
                $portMax = 27095;
                break;
            default:
                break;
        }

        switch ($server->egg->name) {
            case "Minecraft":
                $portMin = 25505;
                $portMax = 25555;
                break;
            case "Rust":
                $portMin = 28025;
                $portMax = 28095;
                break;
            case "FiveM":
                $portMin = 30125;
                $portMax = 30195;
                break;
            default:
                break;
        }

        $requiredAllocations = $server->allocations->count();

        // Get the list of notifications
        $allocations = $this->findAllocations($node, $requiredAllocations, $portMin, $portMax);
        if (count($allocations) <= 0) {
            throw new DisplayException("All available ports where reserved, try transferring again.");
        } else if (count($allocations) != $requiredAllocations) {
            Log::info("findAllocations failed to return the correct amount of allocations", [
                'count' => $allocations,
                'required' => $requiredAllocations
            ]);

            throw new DisplayException("Failed to allocation enough ports, try transferring again.");
        } else {
            $primary_allocation_id = array_shift($allocations);
            $additional_allocations = $allocations;
        }

        // Check that an allocation was found
        if ($primary_allocation_id == null) {
            Log::info("failed to find free allocation on node", [
                'node' => $node
            ]);

            throw new DisplayException('Failed to find node in location with free allocation.');
        }

        // Check if the node is viable for the transfer.
        $node = $this->nodeRepository->getNodeWithResourceUsage($node->id);
        if ($node->isViable($server->memory, $server->disk)) {
            // Check if the selected daemon is online.
            $this->daemonConfigurationRepository->setNode($node)->getSystemInformation();

            // Create a new ServerTransfer entry.
            $transfer = new ServerTransfer;

            $transfer->server_id = $server->id;
            $transfer->old_node = $server->node_id;
            $transfer->new_node = $node->id;
            $transfer->old_allocation = $server->allocation_id;
            $transfer->new_allocation = $primary_allocation_id;
            $transfer->old_additional_allocations = $server->allocations->where('id', '!=', $server->allocation_id)->pluck('id');
            $transfer->new_additional_allocations = $additional_allocations;

            $transfer->save();

            // Add the allocations to the server so they cannot be automatically assigned while the transfer is in progress.
            $this->assignAllocationsToServer($server, $node->id, $primary_allocation_id, $additional_allocations);

            // Re-create the server subdomain with the new allocation id
            // Delete any subdomains
            $this->subdomainDeletionService->delete($server->id, $server->egg_id);

            // Auto generate a subdomain for the new primary allocation
            $domainName = 'iceline.host';
            if ($server->egg->nest->name === 'FiveM') {
                $domainName = 'fivem.host';
            }

            $subdomain = sprintf('%s-%s', $node->location->short, $server->uuidShort);

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
                $allocation = $node->allocations()->where('id', '=', $primary_allocation_id)->get();
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

            // Request an archive from the server's current daemon. (this also checks if the daemon is online)
            $this->transferService->requestArchive($server);
        } else {
            throw new DisplayException("The location you selected is not viable for this transfer."); // trans('admin/server.alerts.transfer_not_viable'));

            /* return response()->json([
                'success' => false,
                'data' => [],
                'error' => [
                    'message' => trans('admin/server.alerts.transfer_not_viable'),
                ]
            ], 400); */
        }

        // Log
        /* $user = $request->user();
        activity()
            ->causedBy($user)
            ->performedOn($server)
            ->withProperties([
                'serverID' => $server->id,
                'module' => 'Server Transfer',
                'type' => 'Started',
                'status' => 'Danger'
            ])
            ->log($user->name_first.' '.$user->name_last.' started transferring the server to "'.$location->long.'('.$location->short.')".'); */

        return response()->json([
            'success' => true,
            'data' => [
                'message' => trans('admin/server.alerts.transfer_started')
            ],
        ], 200);
    }

    /**
     * @param Server $server
     * @param Location $location
     * @param int $requiredAllocations
     * @param int $portMin
     * @param int $portMax
     * @return Node|null
     */
    protected function findNode(Server $server, Location $location, int $requiredAllocations, int $portMin = 0, int $portMax = 65535): ?Node
    {
        Log::info("attempting to find node with available allocations", [
            'location' => $location->short,
            'required_allocations' => $requiredAllocations,
            'port_min' => $portMin,
            'port_max' => $portMax,
        ]);

        foreach ($location->nodes as $node) {
            // Check if the node is even a viable target
            $node = $this->nodeRepository->getNodeWithResourceUsage($node->id);
            if (!$node->isViable($server->memory, $server->disk)) {
                continue;
            }

            Log::info("checking node for allocations", [
                'location' => $location->short,
                'node' => $node->name
            ]);

            // Check for free allocations on the node
            $allocations = $node->allocations()->whereNull('server_id')->whereBetween('port', array($portMin, $portMax))->get();
            Log::info("retrieved suitable allocations for node", [
                'location' => $location->short,
                'node' => $node->name,
                'allocations' => $allocations
            ]);

            if ($allocations->count() > 0 && $allocations->count() >= $requiredAllocations) {
                Log::info("found node with an available allocations", [
                    'location' => $location->short,
                    'node' => $node->name,
                    'allocation_count' => count($allocations)
                ]);

                return $node;
            }
        }

        return null;
    }

    /**
     * @param Node $node
     * @param int $requiredAllocations
     * @param int $portMin
     * @param int $portMax
     * @return array
     * @throws DisplayException
     */
    protected function findAllocations(Node $node, int $requiredAllocations, int $portMin = 0, int $portMax = 65535)
    {
        // Get the list of allocations
        $allocations = $node->allocations()->whereNull('server_id')->whereBetween('port', array($portMin, $portMax))->get();
        if ($allocations->count() <= 0 || $allocations->count() < $requiredAllocations) {
            throw new DisplayException("All available ports where reserved, try transferring again.");
        }

        $allocation_ids = [];
        for ($i = 0; $i < $requiredAllocations; $i++) {
            $allocation_ids[] = $allocations[$i]->id;
        }

        Log::info("retrieved suitable node allocations", [
            'node' => $node->name,
            'allocation_ids' => $allocation_ids
        ]);

        return $allocation_ids;
    }

    /**
     * Assigns the specified allocations to the specified server.
     *
     * @param Server $server
     * @param int $node_id
     * @param int $allocation_id
     * @param array $additional_allocations
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
