<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Location;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\MountFormRequest;
use Pterodactyl\Repositories\Eloquent\MountRepository;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class MountController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $nestRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $repository;

    /**
     * MountController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        NestRepositoryInterface $nestRepository,
        LocationRepositoryInterface $locationRepository,
        MountRepository $repository
    ) {
        $this->alert = $alert;
        $this->nestRepository = $nestRepository;
        $this->locationRepository = $locationRepository;
        $this->repository = $repository;
    }

    /**
     * Return the mount overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mounts != 1 && $role->p_mounts != 2) {return abort(403);}}}

        return view('admin.mounts.index', [
            'mounts' => $this->repository->getAllWithDetails(),
        ]);
    }

    /**
     * Return the mount view page.
     *
     * @param string $id
     *
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function view($id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mounts != 1 && $role->p_mounts != 2) {return abort(403);}}}

        $nests = Nest::query()->with('eggs')->get();
        $locations = Location::query()->with('nodes')->get();

        return view('admin.mounts.view', [
            'mount' => $this->repository->getWithRelations($id),
            'nests' => $nests,
            'locations' => $locations,
        ]);
    }

    /**
     * Handle request to create new mount.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(MountFormRequest $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_mounts != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $model = (new Mount())->fill($request->validated());
        $model->forceFill(['uuid' => Uuid::uuid4()->toString()]);

        $model->saveOrFail();
        $mount = $model->fresh();

        $this->alert->success('Mount was created successfully.')->flash();

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Handle request to update or delete location.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function update(MountFormRequest $request, Mount $mount)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_mounts != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        if ($request->input('action') === 'delete') {
            return $this->delete($mount);
        }

        $mount->forceFill($request->validated())->save();

        $this->alert->success('Mount was updated successfully.')->flash();

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Delete a location from the system.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function delete(Mount $mount)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_mounts != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $mount->delete();

        return redirect()->route('admin.mounts');
    }

    /**
     * Adds eggs to the mount's many to many relation.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addEggs(Request $request, Mount $mount)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_mounts != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $validatedData = $request->validate([
            'eggs' => 'required|exists:eggs,id',
        ]);

        $eggs = $validatedData['eggs'] ?? [];
        if (count($eggs) > 0) {
            $mount->eggs()->attach($eggs);
        }

        $this->alert->success('Mount was updated successfully.')->flash();

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Adds nodes to the mount's many to many relation.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNodes(Request $request, Mount $mount)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_mounts != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $data = $request->validate(['nodes' => 'required|exists:nodes,id']);

        $nodes = $data['nodes'] ?? [];
        if (count($nodes) > 0) {
            $mount->nodes()->attach($nodes);
        }

        $this->alert->success('Mount was updated successfully.')->flash();

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Deletes an egg from the mount's many to many relation.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteEgg(Mount $mount, int $egg_id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_mounts != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $mount->eggs()->detach($egg_id);

        return response('', 204);
    }

    /**
     * Deletes an node from the mount's many to many relation.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteNode(Mount $mount, int $node_id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_mounts != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $mount->nodes()->detach($node_id);

        return response('', 204);
    }
}
