<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Node;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Nodes\NodeDeletionService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Node\AllocationFormRequest;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Node\AllocationAliasFormRequest;

class NodesController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Allocations\AllocationDeletionService
     */
    protected $allocationDeletionService;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    protected $assignmentService;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeUpdateService
     */
    protected $updateService;

    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    protected $versionService;

    /**
     * @var DaemonConfigurationRepository
     */
    protected $daemonConfigurationRepository;

    /**
     * @var DaemonPowerRepository
     */
    protected $daemonPowerRepository;

    /**
     * @param AlertsMessageBag $alert
     * @param AllocationDeletionService $allocationDeletionService
     * @param AllocationRepositoryInterface $allocationRepository
     * @param AssignmentService $assignmentService
     * @param CacheRepository $cache
     * @param NodeCreationService $creationService
     * @param NodeDeletionService $deletionService
     * @param LocationRepositoryInterface $locationRepository
     * @param NodeRepositoryInterface $repository
     * @param ServerRepositoryInterface $serverRepository
     * @param NodeUpdateService $updateService
     * @param SoftwareVersionService $versionService
     * @param DaemonConfigurationRepository $daemonConfigurationRepository
     * @param DaemonPowerRepository $daemonPowerRepository
     */
    public function __construct(
        AlertsMessageBag              $alert,
        AllocationDeletionService     $allocationDeletionService,
        AllocationRepositoryInterface $allocationRepository,
        AssignmentService             $assignmentService,
        CacheRepository               $cache,
        NodeCreationService           $creationService,
        NodeDeletionService           $deletionService,
        LocationRepositoryInterface   $locationRepository,
        NodeRepositoryInterface       $repository,
        ServerRepositoryInterface     $serverRepository,
        NodeUpdateService             $updateService,
        SoftwareVersionService        $versionService,
        DaemonConfigurationRepository $daemonConfigurationRepository,
        DaemonPowerRepository         $daemonPowerRepository
    )
    {
        $this->alert = $alert;
        $this->allocationDeletionService = $allocationDeletionService;
        $this->allocationRepository = $allocationRepository;
        $this->assignmentService = $assignmentService;
        $this->cache = $cache;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->locationRepository = $locationRepository;
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->updateService = $updateService;
        $this->versionService = $versionService;
        $this->daemonConfigurationRepository = $daemonConfigurationRepository;
        $this->daemonPowerRepository = $daemonPowerRepository;
    }

    /**
     * Displays create new node page.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function create()
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 1 && $role->p_nodes != 2) {
                    return abort(403);
                }
            }
        }

        $locations = $this->locationRepository->all();
        if (count($locations) < 1) {
            $this->alert->warning(trans('admin/node.notices.location_required'))->flash();

            return redirect()->route('admin.locations');
        }

        return view('admin.nodes.new', ['locations' => $locations]);
    }

    /**
     * Post controller to create a new node on the system.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(NodeFormRequest $request)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $node = $this->creationService->handle($request->validated());
        $this->alert->info(trans('admin/node.notices.node_created'))->flash();

        return redirect()->route('admin.nodes.view.allocation', $node->id);
    }

    /**
     * Updates settings for a node.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateSettings(NodeFormRequest $request, Node $node)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $this->updateService->handle($node, $request->validated(), $request->input('reset_secret') === 'on');
        $this->alert->success(trans('admin/node.notices.node_updated'))->flash();

        return redirect()->route('admin.nodes.view.settings', $node->id)->withInput();
    }

    /**
     * Removes a single allocation from a node.
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveSingle(int $node, Allocation $allocation): Response
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $this->allocationDeletionService->handle($allocation);

        return response('', 204);
    }

    /**
     * Removes multiple individual allocations from a node.
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveMultiple(Request $request, int $node): Response
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $allocations = $request->input('allocations');
        foreach ($allocations as $rawAllocation) {
            $allocation = new Allocation();
            $allocation->id = $rawAllocation['id'];
            $this->allocationRemoveSingle($node, $allocation);
        }

        return response('', 204);
    }

    /**
     * Remove all allocations for a specific IP at once on a node.
     *
     * @param int $node
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function allocationRemoveBlock(Request $request, $node)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $this->allocationRepository->deleteWhere([
            ['node_id', '=', $node],
            ['server_id', '=', null],
            ['ip', '=', $request->input('ip')],
        ]);

        $this->alert->success(trans('admin/node.notices.unallocated_deleted', ['ip' => $request->input('ip')]))
            ->flash();

        return redirect()->route('admin.nodes.view.allocation', $node);
    }

    /**
     * Sets an alias for a specific allocation on a node.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function allocationSetAlias(AllocationAliasFormRequest $request)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $this->allocationRepository->update($request->input('allocation_id'), [
            'ip_alias' => (empty($request->input('alias'))) ? null : $request->input('alias'),
        ]);

        return response('', 204);
    }

    /**
     * Creates new allocations on a node.
     *
     * @param int|\Pterodactyl\Models\Node $node
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function createAllocation(AllocationFormRequest $request, Node $node)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $this->assignmentService->handle($node, $request->normalize());
        $this->alert->success(trans('admin/node.notices.allocations_added'))->flash();

        return redirect()->route('admin.nodes.view.allocation', $node->id);
    }

    /**
     * Deletes a node from the system.
     *
     * @param $node
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($node)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_nodes != 2) {
                    $this->alert->danger('You do not have permission to perform this action.')->flash();

                    return redirect()->route('admin.index');
                }
            }
        }

        $this->deletionService->handle($node);
        $this->alert->success(trans('admin/node.notices.node_deleted'))->flash();

        return redirect()->route('admin.nodes');
    }

    /**
     * @param Node $node
     * @return array
     */
    public function allServerRestart(Node $node)
    {
        foreach ($node->servers()->whereNull('status')->get() as $server) {
            try {
                $this->daemonPowerRepository->setServer($server)->send('restart');
            } catch (DaemonConnectionException $e) {
                continue;
            }
        }

        return [];
    }

    /**
     * @param Node $node
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function reboot(Node $node)
    {
        $this->daemonConfigurationRepository->setNode($node)->reboot();

        return [];
    }

    /**
     * @param Node $node
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function hardReboot(Node $node)
    {
        $this->daemonConfigurationRepository->setNode($node)->hardreboot();

        return [];
    }

    /**
     * @param Node $node
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function shutdown(Node $node)
    {
        $this->daemonConfigurationRepository->setNode($node)->shutdown();

        return [];
    }

    /**
     * @param Node $node
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function hardShutdown(Node $node)
    {
        $this->daemonConfigurationRepository->setNode($node)->hardshutdown();

        return [];
    }
}
