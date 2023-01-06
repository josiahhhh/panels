<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Alert\AlertRequest;
use Pterodactyl\Http\Requests\Admin\Alert\DeleteAlertRequest;

class AlertController extends Controller
{
    /**
     * @var AlertsMessageBag
     */
    protected $alert;

    /**
     * @param AlertsMessageBag $alert
     */
    public function __construct(AlertsMessageBag $alert)
    {
        $this->alert = $alert;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('admin.alert', [
            'alerts' => DB::table('alerts')->get(),
            'nodes' => DB::table('nodes')->get(),
        ]);
    }

    /**
     * @param AlertRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(AlertRequest $request)
    {
        DB::table('alerts')->insert([
            'message' => trim($request->input('message')),
            'type' => trim(strip_tags($request->input('type'))),
            'node_ids' => json_encode($request->input('node_ids')),
            'delete_when_expired' => (int) $request->input('delete_when_expired', 1),
            'created_at' => $request->input('created_at'),
            'expire_at' => $request->input('expire_at'),
        ]);

        $this->alert->success('You have successfully created this alert!')->flash();

        return redirect()->route('admin.alert');
    }

    /**
     * @param AlertRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     */
    public function edit(AlertRequest $request, $id)
    {
        $alert = DB::table('alerts')->where('id', '=', $id)->get();
        if (count($alert) < 1) {
            throw new DisplayException('Alert not found.');
        }

        DB::table('alerts')->where('id', '=', $id)->update([
            'message' => trim($request->input('message')),
            'type' => trim(strip_tags($request->input('type'))),
            'node_ids' => json_encode($request->input('node_ids')),
            'delete_when_expired' => (int) $request->input('delete_when_expired', 1),
            'created_at' => $request->input('created_at'),
            'expire_at' => $request->input('expire_at'),
        ]);

        $this->alert->success('You have successfully edited this alert!')->flash();

        return redirect()->route('admin.alert');
    }

    /**
     * @param DeleteAlertRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteAlertRequest $request)
    {
        DB::table('alerts')->where('id', '=', $request->input('id'))->delete();

        return response()->json(['success' => true]);
    }
}
