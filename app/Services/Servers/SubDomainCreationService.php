<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

class SubDomainCreationService {
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settingsRepository;

    /**
     * SubDomainDeletionService constructor.
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(
        SettingsRepositoryInterface $settings
    ) {
        $this->settingsRepository = $settings;
    }

    /**
     * @param $server_id
     * @param $egg_id
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function create(string $subdomain, int $domain_id, Server $server, Collection $allocation = null) {
        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $domain_id)->get();
        if (count($domain) < 1) {
            throw new DisplayException('Domain not found.');
        }

        $protocol = unserialize($domain[0]->protocol);
        $protocol = $protocol[$server->egg_id];

        $type = unserialize($domain[0]->protocol_types);
        $type = empty($type[$server->egg_id]) || !isset($type[$server->egg_id]) ? 'tcp' : $type[$server->egg_id];

        $subdomainCount = DB::table('subdomain_manager_subdomains')->where('server_id', '=', $server->id)->get();
        if (count($subdomainCount) >= $this->settingsRepository->get('settings::subdomain::max_subdomain', 1)) {
            throw new DisplayException('You can create maximum ' . $this->settingsRepository->get('settings::subdomain::max_subdomain', 1) . ' Subdomain.');
        }

        if(preg_match("/^[a-zA-Z0-9\-]+$/", $subdomain) != 1) {
            throw new DisplayException('Invalid domain name format ' . $subdomain . '. The domain only contains: [a-z] [A-Z] [0-9] and dashes');
        }

        $subdomainIsset = DB::table('subdomain_manager_subdomains')->where('domain_id', '=', $domain_id)->where('subdomain', '=', $subdomain)->get();
        if (count($subdomainIsset) > 0) {
            throw new DisplayException('This subdomain is already taken: ' . $subdomain);
        }

        if ($allocation === null) {
            $allocation = DB::table('allocations')->where('id', '=', $server->allocation_id)->get();
        }

        try {
            $key = new \Cloudflare\API\Auth\APIKey(
                $this->settingsRepository->get('settings::subdomain::cf_email', ''),
                $this->settingsRepository->get('settings::subdomain::cf_api_key', '')
            );
            $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
            $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
            $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

            $zoneID = $zones->getZoneID($domain[0]->domain);
        } catch (\Exception $e) {
            throw new DisplayException('Failed to connect to cloudflare server.');
        }

        if (empty($protocol)) {
            $subdomain_all = $subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'CNAME', $subdomain_all)->result;

            if (count($result) > 0) {
                throw new DisplayException('This subdomain is already taken: ' . $subdomain);
            }

            $data = array(
                'type' => 'CNAME',
                'name' => $subdomain,
                'content' => $allocation[0]->ip_alias,
                'proxiable' => false,
                'proxied' => false,
                'ttl' => 120
            );

            try {
                if ($dns->addRecord($zoneID, 'CNAME', $subdomain, $allocation[0]->ip_alias, 120, false, '', [
                        'proxiable' => false,
                    ]) !== true) {
                    Log::error('failed to create subdomain', [
                        'body' => $dns->getBody()
                    ]);

                    throw new DisplayException('Failed to create subdomain.');
                }
            } catch (\Exception $e) {
                Log::error('exception occurred creating cname subdomain', [
                    'body' => $dns->getBody(),
                    'ex' => $e
                ]);

                throw new DisplayException('Failed to create subdomain.');
            }
        } else {
            $subdomain_all = $protocol . '._' . $type . '.' . $subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'SRV', $subdomain_all)->result;

            if (count($result) > 0) {
                throw new DisplayException('This subdomain is already taken: ' . $subdomain);
            }

            $data = array(
                'type' => 'SRV',
                'data' => array(
                    "name" => $subdomain,
                    "ttl" => 120,
                    "service" => $protocol,
                    "proto" => "_" . $type,
                    "weight" => 1,
                    "port" => $allocation[0]->port,
                    "priority" => 1,
                    "target" => $allocation[0]->ip_alias,
                )
            );

            try {
                if ($dns->addRecord($zoneID, 'SRV', '', '', 120, false, '', [
                        "service" => $protocol,
                        "proto" => "_" . $type,
                        "weight" => 1,
                        "priority" => 1,
                        "name" => $subdomain,
                        "port" => $allocation[0]->port,
                        "target" => $allocation[0]->ip_alias,
                    ]) !== true) {
                    Log::error('failed to create subdomain', [
                        'body' => $dns->getBody()
                    ]);

                    throw new DisplayException('Failed to create subdomain.');
                }
            } catch (\Exception $e) {
                Log::error('exception occurred creating srv subdomain', [
                    'body' => $dns->getBody(),
                    'ex' => $e
                ]);

                throw new DisplayException('Failed to create subdomain.');
            }
        }

        DB::table('subdomain_manager_subdomains')->insert([
            'server_id' => $server->id,
            'domain_id' => $domain_id,
            'subdomain' => $subdomain,
            'port' => $allocation[0]->port,
            'record_type' => empty($protocol) ? 'CNAME' : 'SRV',
            'deletable' => false
        ]);
    }
}

?>
