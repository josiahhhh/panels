<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\LocationFormRequest;
use Pterodactyl\Services\Locations\LocationUpdateService;
use Pterodactyl\Services\Locations\LocationCreationService;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Locations\LocationCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Locations\LocationDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Locations\LocationUpdateService
     */
    protected $updateService;

    /**
     * LocationController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        LocationCreationService $creationService,
        LocationDeletionService $deletionService,
        LocationRepositoryInterface $repository,
        LocationUpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Return the location overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_locations != 1 && $role->p_locations != 2) {return abort(403);}}}

        return view('admin.locations.index', [
            'locations' => $this->repository->getAllWithDetails(),
        ]);
    }

    /**
     * Return the location view page.
     *
     * @param int $id
     *
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function view($id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_locations != 1 && $role->p_locations != 2) {return abort(403);}}}

        $eggs = DB::table('eggs')->get();

        return view('admin.locations.view', [
            'location' => $this->repository->getWithNodes($id),
            'eggs' => $eggs
        ]);
    }

    /**
     * Handle request to create new location.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(LocationFormRequest $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_locations != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $location = $this->creationService->handle($request->normalize());
        $this->alert->success('Location was created successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Handle request to update or delete location.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function update(LocationFormRequest $request, Location $location)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_locations != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        if ($request->input('action') === 'delete') {
            return $this->delete($location);
        }

        $data = $request->normalize();
        if ($request->has('user_transferable')) {
            $data['user_transferable'] = true;
        } else {
            $data['user_transferable'] = false;
        }

        $transfer_allowed_egg_ids = $request->input('transfer_allowed_egg_ids');
        if (!is_null($transfer_allowed_egg_ids) && count($transfer_allowed_egg_ids) > 0) {
            $data['transfer_allowed_egg_ids'] = implode(',', $transfer_allowed_egg_ids);
        } else {
            $data['transfer_allowed_egg_ids'] = '';
        }

        $this->updateService->handle($location->id, $data);
        $this->alert->success('Location was updated successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Delete a location from the system.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(Location $location)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_locations != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        try {
            $this->deletionService->handle($location->id);

            return redirect()->route('admin.locations');
        } catch (DisplayException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.locations.view', $location->id);
    }
}
