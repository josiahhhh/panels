<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Iceline\IPCheckService;

class IPCheckerController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var IPCheckService
     */
    private $ipCheckService;

    /**
     * IPCheckerController constructor.
     * @param AlertsMessageBag $alert
     */
    public function __construct(AlertsMessageBag $alert, IPCheckService $ipCheckService)
    {
        $this->alert = $alert;

        $this->ipCheckService = $ipCheckService;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin.ipchecker', []);
    }

    function remove_duplicate_allocations( $allocations ) {
        $ips = array_map( function( $allocation ) {
            return $allocation->ip;
        }, $allocations );

        $unique_ips = array_unique( $ips );

        return array_values( array_intersect_key( $allocations, $unique_ips ) );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function checkIPs(Request $request)
    {
        Log::info('starting ip check');

        $this->validate($request, [
            'port' => 'required|integer',
        ]);

        // Retrieve any blacklisted allocations
        $blacklistedAllocations = $this->ipCheckService->handle((int) $request->input('port', 0));

        Log::info('finished ip check');
        $this->alert->success('All ip successfully checked.')->flash();

        return view('admin.ipchecker.results', [
            'allocations' => $blacklistedAllocations
        ]);
    }
}
