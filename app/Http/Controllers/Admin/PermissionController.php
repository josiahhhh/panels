<?php

namespace Pterodactyl\Http\Controllers\Admin;


use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;


class PermissionController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * CreateServerController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     */
    public function __construct(AlertsMessageBag $alert) {$this->alert = $alert;}

    public function index()
    {
        $roles = DB::table('permissions')->get();
        $roles2 = DB::table('permissions')->get();foreach($roles2 as $role) {if($role->id == Auth::user()->role) {if($role->p_permissions != 1 && $role->p_permissions != 2) {return abort(403);}}}

        return view('admin.permissions.index', [
            'roles' => $roles,
        ]);
    }

    public function new()
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_permissions != 1 && $role->p_permissions != 2) {return abort(403);}}}

        return view('admin.permissions.new');
    }

    public function create(Request $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_permissions != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        DB::table('permissions')->insert([
            'name' => $request->name,
            'color' => $request->color,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
            'p_settings' => $request->settings,
            'p_api' => $request->api,
            'p_permissions' => $request->permissions,
            'p_databases' => $request->databases,
            'p_locations' => $request->locations,
            'p_nodes' => $request->nodes,
            'p_servers' => $request->servers,
            'p_users' => $request->users,
            'p_mounts' => $request->mounts,
            'p_nests' => $request->nests,
            'p_addresses' => $request->addresses,
            'p_auto_allocation_adder' => $request->auto_allocation_adder,
            'p_subdomains' => $request->subdomains,
            'p_mods' => $request->mods,
            'p_backups' => $request->backups
        ]);

        $this->alert->success(
            trans('Role successfully created.')
        )->flash();

        return RedirectResponse::create('/admin/permissions/');
    }

    public function edit(Permission $permission)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_permissions != 1 && $role->p_permissions != 2) {return abort(403);}}}

        $settings = $permission->p_settings;
        $api = $permission->p_api;
        $permissions = $permission->p_permissions;
        $databases = $permission->p_databases;
        $locations = $permission->p_locations;
        $nodes = $permission->p_nodes;
        $servers = $permission->p_servers;
        $users = $permission->p_users;
        $mounts = $permission->p_mounts;
        $nests = $permission->p_nests;
        $addresses = $permission->p_addresses;
        $auto_allocation_adder = $permission->p_auto_allocation_adder;
        $subdomains = $permission->p_subdomains;
        $mods = $permission->p_mods;
        $backups = $permission->p_backups;

        return view('admin.permissions.edit', [
            'role' => $permission,
            'settings' => $settings,
            'api' => $api,
            'permissions' => $permissions,
            'databases' => $databases,
            'locations' => $locations,
            'nodes' => $nodes,
            'servers' => $servers,
            'users' => $users,
            'mounts' => $mounts,
            'nests' => $nests,
            'addresses' => $addresses,
            'auto_allocation_adder' => $auto_allocation_adder,
            'subdomains' => $subdomains,
            'mods' => $mods,
            'backups' => $backups
        ]);
    }

    public function update(Request $request, $id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id == Auth::user()->role) {if($role->p_permissions != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        DB::table('permissions')->where('id', '=', $id)->update([
            'name' => $request->name,
            'color' => $request->color,
            'updated_at' => \Carbon\Carbon::now(),
            'p_settings' => $request->settings,
            'p_api' => $request->api,
            'p_permissions' => $request->permissions,
            'p_databases' => $request->databases,
            'p_locations' => $request->locations,
            'p_nodes' => $request->nodes,
            'p_servers' => $request->servers,
            'p_users' => $request->users,
            'p_mounts' => $request->mounts,
            'p_nests' => $request->nests,
            'p_addresses' => $request->addresses,
            'p_auto_allocation_adder' => $request->auto_allocation_adder,
            'p_subdomains' => $request->subdomains,
            'p_mods' => $request->mods,
            'p_backups' => $request->backups
        ]);

        $this->alert->success(
            trans('Role successfully updated.')
        )->flash();

        return RedirectResponse::create('/admin/permissions/');
    }

    public function destroy($id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role){if($role->id = Auth::user()->role) {if($role->p_permissions != 2) {$this->alert->danger('You do not have permission to perform this action.')->flash();return redirect()->route('admin.index');}}}

        $users = DB::table('users')->get();
        foreach($users as $user) {
            if($user->role == $id) {
                $this->alert->danger(
                    trans('This role is still in use by one or more users.')
                )->flash();
                return RedirectResponse::create('/admin/permissions/');
            }

        }


        DB::table('permissions')->where('id', '=', $id)->delete();

        $this->alert->success(
            trans('Role successfully deleted.')
        )->flash();

        return RedirectResponse::create('/admin/permissions/');
    }
}
