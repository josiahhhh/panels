<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ModCategoriesController extends Controller {

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;


    /**
     * SubDomainController constructor.
     * @param AlertsMessageBag $alert
     */
    public function __construct(AlertsMessageBag $alert) {
        $this->alert = $alert;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        $categories = DB::table('mod_manager_categories')->get();

        return view('admin.mods.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new() {
        $categories = DB::table('mod_manager_categories')->get();

        return view('admin.mods.categories.new', [
            'categories' => $categories
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request) {
        $this->validate($request, [
            'name' => 'required|min:1|max:100'
        ]);

        $name = trim(strip_tags($request->input('name')));

        DB::table('mod_manager_categories')->insert([
            'name' => $name,
        ]);

        $this->alert->success('You have successfully created new mod category.')->flash();
        return redirect()->route('admin.mods');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|\Illuminate\View\View
     */
    public function edit($id) {
        $id = (int) $id;

        $category = DB::table('mod_manager_categories')->where('id', '=', $id)->get();
        if (count($category) < 1) {
            $this->alert->danger('Category not found!')->flash();
            return redirect()->route('admin.mods');
        }

        return view('admin.mods.categories.edit', [
            'category' => $category[0],
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return SER__%% \Illuminate\Http\JsonResponse|RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id) {
        $id = (int) $id;

        $category = DB::table('mod_manager_categories')->where('id', '=', $id)->get();
        if (count($category) < 1) {
            $this->alert->danger('Category not found.')->flash();
            return redirect()->route('admin.mods');
        }

        $this->validate($request, [
            'name' => 'required|min:1|max:100'
        ]);

        $name = trim(strip_tags($request->input('name')));

        DB::table('mod_manager_categories')->where('id', '=', $id)->update([
            'name' => $name,
        ]);

        $this->alert->success('You have successfully edited this category.')->flash();
        return redirect()->route('admin.mods.categories.edit', $id);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request) {
        $category_id = (int) $request->input('id', '');

        $category = DB::table('mod_manager_categories')->where('id', '=', $category_id)->get();
        if (count($category) < 1) {
            return response()->json(['success' => false, 'error' => 'Category not found.']);
        }

        DB::table('mod_manager_categories')->where('id', '=', $category_id)->delete();

        return response()->json(['success' => true]);
    }
}
