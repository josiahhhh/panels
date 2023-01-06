<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Models\Egg;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\Facades\Alert;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\FilterService;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FilterController extends Controller
{
    /**
     * @var SettingsRepository
     */
    protected $settingsRepository;

    /**
     * @param SettingsRepository $settingsRepository
     */
    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $types = FilterService::getFilters();

        $servers = Server::query()
            ->select(['servers.*', 'allocations.ip as ip', 'allocations.port as port'])
            ->whereNotNull('filter')
            ->where(function (Builder $query) use ($request) {
                if (!empty($request->input('queryFilter'))) {
                    return $query->whereJsonContains('filter->type', $request->input('queryFilter'));
                }

                return $query;
            })
            ->leftJoin('allocations', 'allocations.id', '=', 'servers.allocation_id')
            ->get()
            ->toArray();

        foreach ($servers as $key => $server) {
            $filter = json_decode($server['filter'], true);

            foreach ($types as $type) {
                if ($type['name'] == $filter['type']) {
                    $filter['type'] = $type['label'];
                }
            }

            $servers[$key]['filter'] = $filter;
        }

        return view('admin.filter', [
            'filters' => FilterService::getAllFilters(),
            'eggs' => Egg::all(),
            'types' => $types !== false ? $types : [],
            'nodes' => Node::all(),
            'excludedNodeIds' => $this->settingsRepository->get('settings::filters::excluded_node_ids', ''),
            'servers' => array_values($servers),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'filter' => ['required', 'string', 'max:255'],
            'egg_ids' => ['required', 'array'],
            'egg_ids.*' => ['integer', 'exists:eggs,id'],
        ]);

        DB::table('filters')->insert([
            'filter' => $request->input('filter'),
            'egg_ids' => implode(',', $request->input('egg_ids', [])),
        ]);

        Alert::success('You\'ve successfully added the new filter.')->flash();

        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, $id)
    {
        $filter = DB::table('filters')->where('id', '=', (int) $id)->first();
        if (!$filter) {
            throw new NotFoundHttpException('Filter not found.');
        }

        return view('admin.filter', [
            'filters' => FilterService::getAllFilters(),
            'eggs' => Egg::all(),
            'types' => FilterService::getFilters(),
            'nodes' => Node::all(),
            'excludedNodeIds' => $this->settingsRepository->get('settings::filters::excluded_node_ids', ''),
            'editFilter' => $filter,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $filter = DB::table('filters')->where('id', '=', (int) $id)->first();
        if (!$filter) {
            throw new NotFoundHttpException('Filter not found.');
        }

        DB::table('filters')->where('id', '=', $filter->id)->update([
            'filter' => $request->input('filter'),
            'egg_ids' => implode(',', $request->input('egg_ids', [])),
        ]);

        Alert::success('You\'ve successfully updated the filter.')->flash();

        return redirect()->route('admin.filters');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => ['required', 'integer', 'exists:filters,id'],
        ]);

        DB::table('filters')->where('id', '=', (int) $request->input('id'))->delete();

        return [];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function push(Request $request)
    {
        $this->validate($request, [
            'id' => ['required', 'integer', 'exists:filters,id'],
        ]);

        $filter = DB::table('filters')->where('id', '=', (int) $request->input('id'))->first();
        foreach (explode(',', $filter->egg_ids) as $eggId) {
            foreach (Server::where('egg_id', '=', $eggId)->whereNull('filter')->get() as $server) {
                FilterService::createFilter($server, $filter->filter);
            }
        }

        return [];
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function save(Request $request)
    {
        $this->validate($request, [
            'excluded_node_ids.*' => ['integer', 'exists:nodes,id'],
        ]);

        $this->settingsRepository->set('settings::filters::excluded_node_ids', implode(',', $request->input('excluded_node_ids', [])));

        Alert::success('You\'ve successfully edited the settings.');

        return back();
    }
}
