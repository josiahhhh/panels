<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;

class IpChangerController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var DaemonPowerRepository
     */
    protected $daemonPowerRepository;

    /**
     * @param AlertsMessageBag $alert
     * @param DaemonPowerRepository $daemonPowerRepository
     */
    public function __construct(AlertsMessageBag $alert, DaemonPowerRepository $daemonPowerRepository)
    {
        $this->alert = $alert;
        $this->daemonPowerRepository = $daemonPowerRepository;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|View
     */
    public function index()
    {
        $nodes = DB::table('nodes')->get();
        $allocations = DB::table('allocations')->orderBy('port', 'ASC')->get();

        return view('admin.ipchanger', ['nodes' => $nodes, 'allocations' => $allocations]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function change(Request $request)
    {
        $this->validate($request, [
            'node' => 'required|integer',
            'lines' => 'required'
        ]);

        $lines = explode(',', trim($request->input('lines')));
        $servers_to_start = [];

        foreach ($lines as $line) {
            $allocation = DB::table('allocations')->where('id', '=', $request->input('allocation-' . $line))->get();
            if (count($allocation) < 1) {
                $this->alert->danger('Allocation not found.')->flash();

                return redirect()->route('admin.ipchange');
            }

            $existsNewAllocation = DB::table('allocations')->where('node_id', '=', $allocation[0]->node_id)->where('ip', '=', trim($request->input('ip-' . $line)))->where('port', '=', $allocation[0]->port)->get();
            if (count($existsNewAllocation) > 0) {
                continue;
            }

            if (!is_null($allocation[0]->server_id)) {
                $server = DB::table('servers')->where('id', '=', $allocation[0]->server_id)->first();

                if ($server && is_null($server->status)) {
                    $servers_to_start[] = $server->id;
                }
            }

            DB::table('allocations')->where('id', '=', $allocation[0]->id)->update([
                'ip' => trim($request->input('ip-' . $line)),
                'ip_alias' => trim($request->input('alias-' . $line)),
            ]);
        }

        foreach ($servers_to_start as $item) {
            $this->daemonPowerRepository->setServer(Server::find($item))->send('restart');
        }

        $this->alert->success('You have successfully changed the IP and alias.')->flash();

        return redirect()->route('admin.ipchange');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function global(Request $request)
    {
        $this->validate($request, [
            'old_ip' => 'required|ip',
            'new_ip' => 'required|ip',
        ]);

        $old_ip = $request->input('old_ip', 0);
        $new_ip = $request->input('new_ip', 0);

        $allocations = DB::table('allocations')->where('ip', '=', $old_ip)->get();
        if (count($allocations) < 1) {
            throw new DisplayException('There aren\'t any allocation found in this ip.');
        }

        $servers_to_start = [];

        foreach ($allocations as $allocation) {
            $existsAllocation = DB::table('allocations')->where('ip', '=', $new_ip)->where('port', '=', $allocation->port)->where('node_id', '=', $allocation->node_id)->where('id', '!=', $allocation->id)->get();
            if (count($existsAllocation) > 0) {
                continue;
            }

            DB::table('allocations')->where('id', '=', $allocation->id)->update([
                'ip' => $new_ip,
                'ip_alias' => empty($request->input('new_alias', '')) ? $allocation->ip_alias : $request->input('new_alias'),
            ]);

            if (!is_null($allocation->server_id)) {
                $server = DB::table('servers')->where('id', '=', $allocation->server_id)->first();

                if ($server && is_null($server->status)) {
                    $servers_to_start[] = $server->id;
                }
            }
        }

        foreach ($servers_to_start as $item) {
            $this->daemonPowerRepository->setServer(Server::find($item))->send('restart');
        }

        $this->alert->success('You have successfully changed the IPs.')->flash();

        return redirect()->route('admin.ipchange');
    }
}
