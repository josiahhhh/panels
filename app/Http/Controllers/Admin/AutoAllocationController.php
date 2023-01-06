<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\AutoAllocationService;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Services\Servers\StartupModificationService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AutoAllocationController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var AutoAllocationService
     */
    protected $autoAllocationService;

    /**
     * @var BuildModificationService
     */
    protected $buildModificationService;

    /**
     * @var StartupModificationService
     */
    protected $startupModificationService;

    /**
     * @param AlertsMessageBag $alert
     * @param AutoAllocationService $autoAllocationService
     * @param BuildModificationService $buildModificationService
     * @param StartupModificationService $startupModificationService
     */
    public function __construct(AlertsMessageBag $alert, AutoAllocationService $autoAllocationService, BuildModificationService $buildModificationService, StartupModificationService $startupModificationService)
    {
        $this->alert = $alert;
        $this->autoAllocationService = $autoAllocationService;
        $this->buildModificationService = $buildModificationService;
        $this->startupModificationService = $startupModificationService;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin.autoallocation.index', [
            'locations' => DB::table('auto_allocation_adder')
                ->select(['auto_allocation_adder.*', 'eggs.name as eggName'])
                ->leftJoin('eggs', 'eggs.id', '=', 'auto_allocation_adder.egg_id')
                ->get(),
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        return view('admin.autoallocation.new', [
            'eggs' => DB::table('eggs')->get(),
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'egg_id' => 'required',
            'allocation_selectors' => 'required'
        ]);

        $egg_id = (int) $request->input('egg_id');
        $allocation_selectors = trim(strip_tags($request->input('allocation_selectors')));

        $egg = DB::table('eggs')->where('id', '=', $egg_id)->get();
        if (count($egg) < 1) {
            $this->alert->danger('Egg not found.')->flash();

            return redirect()->route('admin.autoallocation.new');
        }

        $checkIsset = DB::table('auto_allocation_adder')->where('egg_id', '=', $egg_id)->get();
        if (count($checkIsset) > 0) {
            $this->alert->danger('You already added allocation to this egg.')->flash();

            return redirect()->route('admin.autoallocation.new');
        }

        $selectors = [];

        foreach (explode(',', $allocation_selectors) as $selector) {
            if (empty($request->input('allocation_type' . $selector, ''))) {
                $this->alert->danger('Selector not found. Please try again.')->flash();

                return redirect()->route('admin.autoallocation.new');
            }

            if ($request->input('allocation_type' . $selector) == 'random') {
                $range = explode('-', $request->input('allocation_port' . $selector, ''));
                if (count($range) != 2) {
                    $this->alert->danger('Invalid random range.')->flash();

                    return redirect()->route('admin.autoallocation.new');
                }
            }

            $selectors[] = [
                'type' => $request->input('allocation_type' . $selector),
                'port' => empty($request->input('allocation_port' . $selector, '')) ? 1 : $request->input('allocation_port' . $selector, ''),
                'environment_name' => empty($request->input('environment_name' . $selector, '')) ? '' : $request->input('environment_name' . $selector, ''),
            ];
        }

        DB::table('auto_allocation_adder')->insert([
            'egg_id' => $egg_id,
            'allocations' => serialize($selectors)
        ]);

        $this->alert->success('You have successfully created new allocation.')->flash();

        return redirect()->route('admin.autoallocation');
    }

    /**
     * @param $location_id
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|\Illuminate\View\View
     */
    public function edit($location_id)
    {
        $location_id = (int) $location_id;

        $location = DB::table('auto_allocation_adder')->where('id', '=', $location_id)->get();
        if (count($location) < 1) {
            throw new NotFoundHttpException('Location not found.');
        }

        $key = 0;
        $locations = '';

        foreach (unserialize($location[0]->allocations) as $item) {
            $key = $key + 1;
            empty($locations) ? $locations = $key : $locations = $locations . ',' . $key;
        }

        return view('admin.autoallocation.edit', [
            'location' => $location[0],
            'eggs' => DB::table('eggs')->get(),
            'allocation_selectors' => $locations,
        ]);
    }

    /**
     * @param Request $request
     * @param $location_id
     * @return \Illuminate\Http\JsonResponse|RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $location_id)
    {
        $location_id = (int) $location_id;

        $allocation = DB::table('auto_allocation_adder')->where('id', '=', $location_id)->get();
        if (count($allocation) < 1) {
            throw new NotFoundHttpException('Location not found.');
        }

        $this->validate($request, [
            'egg_id' => 'required',
            'allocation_selectors' => 'required'
        ]);

        $egg_id = (int) $request->input('egg_id');
        $allocation_selectors = trim(strip_tags($request->input('allocation_selectors')));

        $egg = DB::table('eggs')->where('id', '=', $egg_id)->get();
        if (count($egg) < 1) {
            $this->alert->danger('Egg not found.')->flash();

            return redirect()->route('admin.autoallocation.edit', $location_id);
        }

        $checkIsset = DB::table('auto_allocation_adder')->where('egg_id', '=', $egg_id)->where('egg_id', '!=', $allocation[0]->egg_id)->get();
        if (count($checkIsset) > 0) {
            $this->alert->danger('You already added allocation to this egg.')->flash();

            return redirect()->route('admin.autoallocation.edit', $location_id);
        }

        $selectors = [];

        foreach (explode(',', $allocation_selectors) as $selector) {
            if (empty($request->input('allocation_type' . $selector, ''))) {
                $this->alert->danger('Selector not found. Please try again.')->flash();

                return redirect()->route('admin.autoallocation.edit', $location_id);
            }

            if ($request->input('allocation_type' . $selector) == 'random') {
                $range = explode('-', $request->input('allocation_port' . $selector, ''));
                if (count($range) != 2) {
                    $this->alert->danger('Invalid random range.')->flash();

                    return redirect()->route('admin.autoallocation.new');
                }
            }

            $selectors[] = [
                'type' => $request->input('allocation_type' . $selector),
                'port' => empty($request->input('allocation_port' . $selector, '')) ? 1 : $request->input('allocation_port' . $selector, ''),
                'environment_name' => empty($request->input('environment_name' . $selector, '')) ? '' : $request->input('environment_name' . $selector, ''),
            ];
        }

        DB::table('auto_allocation_adder')->where('id', '=', $location_id)->update([
            'egg_id' => $egg_id,
            'allocations' => serialize($selectors)
        ]);

        $this->alert->success('You have successfully edited this allocation.')->flash();

        return redirect()->route('admin.autoallocation.edit', $location_id);
    }

    public function apply(Request $request)
    {
        $location = DB::table('auto_allocation_adder')->where('id', '=', (int) $request->input('id', 0))->first();
        if (!$location) {
            return response()->json(['error' => 'Allocation not found.'])->setStatusCode(500);
        }

        foreach (Server::where('egg_id', '=', $location->egg_id)->get() as $server) {
            if ($server->allocations()->count() < 2) {
                $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);

                $vars = [];
                foreach ($server->variables()->get() as $variable) {
                    $vars[] = [
                        'key' => $variable->env_variable,
                        'value' => $variable->server_value,
                    ];
                }

                $auto_allocation_ids = $this->autoAllocationService->handle($server, json_decode(json_encode($vars)));
                if ($auto_allocation_ids != false) {
                    try {
                        $this->buildModification($server, $auto_allocation_ids['allocations']);
                    } catch (DisplayException|\Throwable $e) {
                        return response()->json(['error' => 'Failed to add allocations to server #' . $server->id])->setStatusCode(500);
                    }

                    $environment = [];
                    foreach ($auto_allocation_ids['eggs'] as $var) {
                        $environment[$var->key] = trim($var->value);
                    }

                    try {
                        $this->startupModificationService->handle($server, [
                            'nest_id' => $server->nest_id,
                            'egg_id' => $server->egg_id,
                            'docker_image' => $server->image,
                            'startup' => $server->startup,
                            'environment' => $environment,
                        ]);
                    } catch (\Throwable $e) {
                        return response()->json(['error' => 'Failed to modify startup variables in server #' . $server->id . $e->getMessage()])->setStatusCode(500);
                    }
                }
            }
        }

        return [];
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $location = DB::table('auto_allocation_adder')->where('id', '=', (int) $request->input('id', 0))->first();
        if (!$location) {
            return response()->json(['error' => 'Allocation not found.'])->setStatusCode(500);
        }

        DB::table('auto_allocation_adder')->where('id', '=', $location->id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @param $server
     * @param $allocations
     * @return void
     * @throws DisplayException
     */
    private function buildModification($server, $allocations)
    {
        try {
            $this->buildModificationService->handle($server, [
                'allocation_limit' => $server->allocation_limit,
                'backup_limit' => $server->backup_limit,
                'database_limit' => $server->database_limit,
                'add_allocations' => $allocations,
            ]);
        } catch (DisplayException|\Throwable $e) {
            throw new DisplayException('Failed to add the allocations.');
        }
    }
}
