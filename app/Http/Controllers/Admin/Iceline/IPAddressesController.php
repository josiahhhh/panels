<?php

namespace Pterodactyl\Http\Controllers\Admin\Iceline;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Iceline\IPAddress;
use Spatie\QueryBuilder\QueryBuilder;

class IPAddressesController extends Controller {

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
     * Returns a list of all types of backups.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        $addresses = QueryBuilder::for(IPAddress::query())->paginate(25);

        return view('admin.ipaddresses.index', [
            'addresses' => $addresses,
        ]);
    }
}
