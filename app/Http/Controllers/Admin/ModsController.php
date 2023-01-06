<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ModsController extends Controller {

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * SubDomainController constructor.
     * @param AlertsMessageBag $alert
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(AlertsMessageBag $alert, SettingsRepositoryInterface $settings) {
        $this->alert = $alert;
        $this->settings = $settings;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mods != 1 && $role->p_mods != 2) {return abort(403);}}}

        $mods = DB::table('mod_manager_mods')->get();
        $mods = json_decode(json_encode($mods), true);

        $eggs = DB::table('eggs')->get();

        $categories = DB::table('mod_manager_categories')->get();
        $categories = json_decode(json_encode($categories), true);

        return view('admin.mods.index', [
            'mods' => $mods,
            'eggs' => $eggs,
            'categories' => $categories,
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new() {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mods != 1 && $role->p_mods != 2) {return abort(403);}}}

        $eggs = DB::table('eggs')->get();
        $categories = DB::table('mod_manager_categories')->get();

        return view('admin.mods.new', [
            'eggs' => $eggs,
            'categories' => $categories
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mods != 2) {return abort(403);}}}

        $this->validate($request, [
            'name' => 'required|min:1|max:100',
            'egg_ids' => 'required'
        ]);

        $name = trim(strip_tags($request->input('name')));
        $description = trim(strip_tags($request->input('description')));
        $mod_zip = trim(strip_tags($request->input('mod_zip')));
        $mod_sql = trim(strip_tags($request->input('mod_sql')));
        $uninstall_paths = trim(strip_tags($request->input('uninstall_paths')));
        $install_folder = trim(strip_tags($request->input('install_folder')));

        $egg_ids = $request->input('egg_ids');
        $categories = $request->input('categories');

        $image = trim(strip_tags($request->input('image')));
        $restart_on_install = $request->has('restart_on_install') || false;

        $disable_cache = $request->has('disable_cache') || false;

        DB::table('mod_manager_mods')->insert([
            'name' => $name,
            'description' => $description,
            'egg_ids' => implode(',', $egg_ids),
            'mod_zip' => $mod_zip,
            'mod_sql' => $mod_sql,
            'uninstall_paths' => $uninstall_paths,
            'install_folder' => $install_folder,
            'categories' => $categories ? implode(',', $categories) : '',
            'image' => $image,
            'restart_on_install' => $restart_on_install,
            'disable_cache' => $disable_cache
        ]);

        $this->alert->success('You have successfully created new mod.')->flash();
        return redirect()->route('admin.mods');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|\Illuminate\View\View
     */
    public function edit($id) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mods != 1 && $role->p_mods != 2) {return abort(403);}}}

        $id = (int) $id;

        $mod = DB::table('mod_manager_mods')->where('id', '=', $id)->get();
        if (count($mod) < 1) {
            $this->alert->danger('Mod not found!')->flash();
            return redirect()->route('admin.mods');
        }

        $eggs = DB::table('eggs')->get();
        $categories = DB::table('mod_manager_categories')->get();

        return view('admin.mods.edit', [
            'mod' => $mod[0],
            'eggs' => $eggs,
            'categories' => $categories
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return SER__%% \Illuminate\Http\JsonResponse|RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mods != 2) {return abort(403);}}}

        $id = (int) $id;

        $mod = DB::table('mod_manager_mods')->where('id', '=', $id)->get();
        if (count($mod) < 1) {
            $this->alert->danger('Mod not found.')->flash();
            return redirect()->route('admin.mods');
        }

        $this->validate($request, [
            'name' => 'required|min:1|max:100',
            'egg_ids' => 'required'
        ]);

        $name = trim(strip_tags($request->input('name')));
        $description = trim(strip_tags($request->input('description')));
        $egg_ids = $request->input('egg_ids');
        $categories = $request->input('categories');
        $mod_zip = trim(strip_tags($request->input('mod_zip')));
        $mod_sql = trim(strip_tags($request->input('mod_sql')));
        $uninstall_paths = trim(strip_tags($request->input('uninstall_paths')));
        $install_folder = trim(strip_tags($request->input('install_folder')));

        $image = trim(strip_tags($request->input('image')));
        $restart_on_install = $request->has('restart_on_install') || false;

        $disable_cache = $request->has('disable_cache') || false;

        DB::table('mod_manager_mods')->where('id', '=', $id)->update([
            'name' => $name,
            'description' => $description,
            'egg_ids' => implode(',', $egg_ids),
            'mod_zip' => $mod_zip,
            'mod_sql' => $mod_sql,
            'uninstall_paths' => $uninstall_paths,
            'install_folder' => $install_folder,
            'categories' => $categories ? implode(',', $categories) : '',
            'image' => $image,
            'restart_on_install' => $restart_on_install,
            'disable_cache' => $disable_cache
        ]);

        $this->alert->success('You have successfully edited this mod.')->flash();
        return redirect()->route('admin.mods.edit', $id);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_mods != 2) {return abort(403);}}}

        $mod_id = (int) $request->input('id', '');

        $mod = DB::table('mod_manager_mods')->where('id', '=', $mod_id)->get();
        if (count($mod) < 1) {
            return response()->json(['success' => false, 'error' => 'Mod not found.']);
        }

        DB::table('mod_manager_mods')->where('id', '=', $mod_id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function settings(Request $request) {

        return redirect()->route('admin.mods');
    }
}
