<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\SubdomainRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SubDomainSyncService;

class SubDomainController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var SubDomainSyncService
     */
    private $subdomainSyncService;

    /**
     * SubDomainController constructor.
     * @param AlertsMessageBag $alert
     * @param SettingsRepositoryInterface $settings
     * @param ServerRepositoryInterface $serverRepository
     * @param SubDomainSyncService $subdomainSyncService
     */
    public function __construct(
        AlertsMessageBag $alert,
        SettingsRepositoryInterface $settings,
        ServerRepositoryInterface $serverRepository,
        SubDomainSyncService $subdomainSyncService
    ) {
        $this->alert = $alert;
        $this->settings = $settings;
        $this->serverRepository = $serverRepository;
        $this->subdomainSyncService = $subdomainSyncService;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 1 && $role->p_subdomains != 2) {return abort(403);}}}

        $domains = DB::table('subdomain_manager_domains')->get();
        $subdomains = DB::table('subdomain_manager_subdomains')->get();

        $domains = json_decode(json_encode($domains), true);
        $subdomains = json_decode(json_encode($subdomains), true);

        foreach ($subdomains as $key => $subdomain) {
            $serverData = DB::table('servers')->select(['id', 'uuidShort', 'name'])->where('id', '=', $subdomain['server_id'])->get();
            if (count($serverData) < 1) {
                $subdomains[$key]['server'] = (object) [
                    'id' => 0,
                    'uuidShort' => '',
                    'name' => 'Not found'
                ];
            } else {
                $subdomains[$key]['server'] = $serverData[0];
            }

            $subdomains[$key]['domain'] = [
                'domain' => 'Not found'
            ];

            foreach ($domains as $domain) {
                if ($domain['id'] == $subdomain['domain_id']) {
                    $subdomains[$key]['domain'] = $domain;
                }
            }
        }

        return view('admin.subdomain.index', [
            'settings' => [
                'cf_email' => $this->settings->get('settings::subdomain::cf_email', ''),
                'cf_api_key' => $this->settings->get('settings::subdomain::cf_api_key', ''),
                'max_subdomain' => $this->settings->get('settings::subdomain::max_subdomain', ''),
            ],
            'domains' => $domains,
            'subdomains' => $subdomains
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 1 && $role->p_subdomains != 2) {return abort(403);}}}

        $eggs = DB::table('eggs')->get();

        return view('admin.subdomain.new', ['eggs' => $eggs]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 2) {return abort(403);}}}

        $this->validate($request, [
            'domain' => 'required|min:1|max:100',
            'egg_ids' => 'required'
        ]);

        $domain = trim(strip_tags($request->input('domain')));
        $egg_ids = $request->input('egg_ids');
        $protocols = [];
        $types = [];

        foreach ($egg_ids as $egg_id) {
            $protocol = $request->input('protocol_for_' . $egg_id, '');
            $type = $request->input('protocol_type_for_' . $egg_id, '');
            $protocols[$egg_id] = $protocol;
            $types[$egg_id] = $type;
        }

        DB::table('subdomain_manager_domains')->insert([
            'domain' => $domain,
            'egg_ids' => implode(',', $egg_ids),
            'protocol' => serialize($protocols),
            'protocol_types' => serialize($types)
        ]);

        $this->alert->success('You have successfully created new domain.')->flash();
        return redirect()->route('admin.subdomain');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|\Illuminate\View\View
     */
    public function edit($id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 1 && $role->p_subdomains != 2) {return abort(403);}}}

        $id = (int) $id;

        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $id)->get();
        if (count($domain) < 1) {
            $this->alert->danger('SubDomain not found!')->flash();
            return redirect()->route('admin.subdomain');
        }

        $eggs = DB::table('eggs')->get();

        return view('admin.subdomain.edit', ['domain' => $domain[0], 'eggs' => $eggs]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return 111507 \Illuminate\Http\JsonResponse|RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 2) {return abort(403);}}}

        $id = (int) $id;

        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $id)->get();
        if (count($domain) < 1) {
            $this->alert->danger('Domain not found.')->flash();
            return redirect()->route('admin.subdomain');
        }

        $this->validate($request, [
            'domain' => 'required|min:1|max:100',
            'egg_ids' => 'required'
        ]);

        $domain = trim(strip_tags($request->input('domain')));
        $egg_ids = $request->input('egg_ids');
        $protocols = [];
        $types = [];

        foreach ($egg_ids as $egg_id) {
            $protocol = $request->input('protocol_for_' . $egg_id, '');
            $type = $request->input('protocol_type_for_' . $egg_id, '');
            $protocols[$egg_id] = $protocol;
            $types[$egg_id] = $type;
        }

        DB::table('subdomain_manager_domains')->where('id', '=', $id)->update([
            'domain' => $domain,
            'egg_ids' => implode(',', $egg_ids),
            'protocol' => serialize($protocols),
            'protocol_types' => serialize($types)
        ]);

        $this->alert->success('You have successfully edited this domain.')->flash();
        return redirect()->route('admin.subdomain.edit', $id);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 2) {return abort(403);}}}

        $domain_id = (int) $request->input('id', '');

        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $domain_id)->get();
        if (count($domain) < 1) {
            return response()->json(['success' => false, 'error' => 'Domain not found.']);
        }

        DB::table('subdomain_manager_domains')->where('id', '=', $domain_id)->delete();
        DB::table('subdomain_manager_subdomains')->where('domain_id', '=', $domain_id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSubdomain(Request $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 2) {return abort(403);}}}

        $id = (int) $request->input('id', '');

        $subdomain = DB::table('subdomain_manager_subdomains')->where('id', '=', $id)->get();
        if (count($subdomain) < 1) {
            throw new DisplayException('Subdomain not found.');
        }

        // TODO: save protocol and type in subdomain record
        //   so we can delete the subdomain without
        //   requiring an active server.
        /** @var Server $server */
        $server = $this->serverRepository->find($subdomain[0]->server_id);
        if ($server == null) {
            throw new DisplayException('Server not found');
        }

        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $subdomain[0]->domain_id)->get();
        if (count($domain) < 1) {
            throw new DisplayException('Domain not found.');
        }

        $protocol = unserialize($domain[0]->protocol);
        $protocol = $protocol[$server->egg_id];

        $type = unserialize($domain[0]->protocol_types);
        $type = empty($type[$server->egg_id]) || !isset($type[$server->egg_id]) ? 'tcp' : $type[$server->egg_id];

        try {
            $key = new \Cloudflare\API\Auth\APIKey(
                $this->settings->get('settings::subdomain::cf_email', ''),
                $this->settings->get('settings::subdomain::cf_api_key', '')
            );
            $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
            $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
            $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

            $zoneID = $zones->getZoneID($domain[0]->domain);
        } catch (\Exception $e) {
            throw new DisplayException('Failed to connect to cloudflare server.');
        }

        if (empty($protocol)) {
            $subdomain_all = $subdomain[0]->subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'CNAME', $subdomain_all)->result;

            if (count($result) < 1) {
                throw new DisplayException('Failed to delete Subdomain.');
            }

            $recordId = $result[0]->id;
        } else {
            $subdomain_all = $protocol . '._' . $type . '.' . $subdomain[0]->subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'SRV', $subdomain_all)->result;

            if (count($result) < 1) {
                throw new DisplayException('Failed to delete Subdomain.');
            }

            $recordId = $result[0]->id;
        }

        try {
            if ($dns->deleteRecord($zoneID, $recordId) !== true) {
                throw new DisplayException('Failed to delete Subdomain.');
            }
        } catch (\Exception $e) {
            throw new DisplayException('Failed to delete Subdomain.');
        }

        DB::table('subdomain_manager_subdomains')->where('id', '=', $id)->where('server_id', '=', $server->id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function settings(Request $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_subdomains != 2) {return abort(403);}}}

        $this->validate($request, [
            'cf_email' => 'required|max:100',
            'cf_api_key' => 'required|max:100',
            'max_subdomain' => 'required|min:0|integer'
        ]);

        $email = trim($request->input('cf_email'));
        $api_key = trim($request->input('cf_api_key'));
        $max_subdomain = trim($request->input('max_subdomain'));

        $this->settings->set('settings::subdomain::cf_email', $email);
        $this->settings->set('settings::subdomain::cf_api_key', $api_key);
        $this->settings->set('settings::subdomain::max_subdomain', $max_subdomain);

        $this->alert->success('You have successfully updated settings.')->flash();
        return redirect()->route('admin.subdomain');
    }

    /**
     * Deletes and re-creates the subdomain to ensure it's pointing to
     * the correct allocation address for the server.
     * @param SubdomainRequest $request
     * @param Server $server
     * @param $id
     * @throws DisplayException
     */
    public function sync(Request $request): array {
        $id = (int) $request->input('id', '');

        $subdomain = DB::table('subdomain_manager_subdomains')->where('id', '=', $id)->get();
        if (count($subdomain) < 1) {
            throw new DisplayException('Subdomain not found.');
        }

        // TODO: save protocol and type in subdomain record
        //   so we can delete the subdomain without
        //   requiring an active server.
        /** @var Server $server */
        $server = $this->serverRepository->find($subdomain[0]->server_id);
        if ($server == null) {
            throw new DisplayException('Server not found');
        }

        if (!$this->subdomainSyncService->sync($server, $id)) {
            throw new DisplayException('Failed to sync subdomain');
        }

        return [
            'success' => true
        ];
    }
}
