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

class SubDomainSyncService {
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
     * @param Server $server
     * @param int $id
     * @return bool
     * @throws DisplayException
     */
    public function sync(Server $server, int $id) {
        $subdomain = DB::table('subdomain_manager_subdomains')->where('id', '=', $id)->where('server_id', '=', $server->id)->first();
        if (is_null($subdomain)) {
            throw new DisplayException('Subdomain not found.');
        }

        /** @var Allocation $allocation */
        $allocation = DB::table('allocations')->where('id', '=', $server->allocation_id)->first();
        if (is_null($allocation)) {
            throw new DisplayException('Failed to get default allocation.');
        }

        if ($subdomain->port != $allocation->port) {
            Log::info('updating subdomain port');

            DB::table('subdomain_manager_subdomains')->where('id', $subdomain->id)->update([
                'port' => $allocation->port
            ]);
        }

        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $subdomain->domain_id)->first();
        if (is_null($domain)) {
            throw new DisplayException('Domain not found.');
        }

        // Determine the protocol.
        $protocol = unserialize($domain->protocol);
        $protocol = $protocol[$server->egg_id];

        // Determine the record type.
        $type = unserialize($domain->protocol_types);
        $type = empty($type[$server->egg_id]) || !isset($type[$server->egg_id]) ? 'tcp' : $type[$server->egg_id];

        // Create the Cloudflare client.
        try {
            $key = new \Cloudflare\API\Auth\APIKey(
                $this->settingsRepository->get('settings::subdomain::cf_email', ''),
                $this->settingsRepository->get('settings::subdomain::cf_api_key', '')
            );
            $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
            $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
            $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

            $zoneID = $zones->getZoneID($domain->domain);
        } catch (\Exception $e) {
            Log::error('failed to connect to cloudflare server', [
                'ex' => $e,
            ]);

            throw new DisplayException('Failed to connect to cloudflare server.');
        }

        { // Attempt to delete the DNS record
            if (empty($protocol)) {
                $subdomain_all = $subdomain->subdomain . '.' . $domain->domain;

                $result = $dns->listRecords($zoneID, 'CNAME', $subdomain_all)->result;

                if (count($result) < 1) {
                    Log::warning('could not find subdomain CNAME record, skipping deletion', [
                        'domain' => $subdomain_all,
                        'result' => $result
                    ]);

                    // Skip to creating if we cannot find the subdomain
                    goto createSubdomain;
                }

                $recordId = $result[0]->id;
            } else {
                $subdomain_all = $protocol . '._' . $type . '.' . $subdomain->subdomain . '.' . $domain->domain;

                $result = $dns->listRecords($zoneID, 'SRV', $subdomain_all)->result;

                if (count($result) < 1) {
                    Log::warning('could not find subdomain CNAME record, skipping deletion', [
                        'domain' => $subdomain_all,
                        'result' => $result
                    ]);

                    // Skip to creating if we cannot find the subdomain
                    goto createSubdomain;
                }

                $recordId = $result[0]->id;
            }

            try {
                if ($dns->deleteRecord($zoneID, $recordId) !== true) {
                    Log::error('error occurred while deleting subdomain record', [
                        'body' => $dns->getBody(),
                    ]);

                    throw new DisplayException('Failed to delete Subdomain record.');
                }
            } catch (\Exception $e) {
                Log::error('exception occurred while deleting subdomain record', [
                    'ex' => $e,
                ]);

                throw new DisplayException('Failed to delete Subdomain.');
            }
        }

    createSubdomain:
        { // Attempt to re-create the DNS record
            if (empty($protocol)) {
                $subdomain_all = $subdomain->subdomain . '.' . $domain->domain;

                $result = $dns->listRecords($zoneID, 'CNAME', $subdomain_all)->result;

                if (count($result) > 0) {
                    Log::error('subdomain is already taken');

                    throw new DisplayException('This subdomain is already taken: ' . $subdomain->subdomain);
                }

                try {
                    if ($dns->addRecord($zoneID, 'CNAME', $subdomain->subdomain, $allocation->ip_alias, 120, false, '', [
                        'proxiable' => false,
                    ]) !== true) {
                        Log::error('error occurred while adding subdomain record', [
                            'body' => $dns->getBody(),
                            'zone' => $zoneID,
                            'subdomain' => $subdomain->subdomain,
                            'target' => $allocation->ip_alias
                        ]);

                        throw new DisplayException('Failed to create subdomain record.');
                    }
                } catch (\Exception $e) {
                    Log::error('exception occurred creating cname subdomain', [
                        'body' => $dns->getBody(),
                        'zone' => $zoneID,
                        'subdomain' => $subdomain->subdomain,
                        'target' => $allocation->ip_alias,
                        'ex' => $e
                    ]);

                    throw new DisplayException('Failed to create subdomain.');
                }
            } else {
                $subdomain_all = $protocol . '._' . $type . '.' . $subdomain->subdomain . '.' . $domain->domain;

                $result = $dns->listRecords($zoneID, 'SRV', $subdomain_all)->result;

                if (count($result) > 0) {
                    Log::error('requested subdomain is already taken');

                    throw new DisplayException('This subdomain is already taken: ' . $subdomain[0]->subdomain);
                }

                try {
                    if ($dns->addRecord($zoneID, 'SRV', '', '', 120, false, '', [
                        "service" => $protocol,
                        "proto" => "_" . $type,
                        "weight" => 1,
                        "priority" => 1,
                        "name" => $subdomain->subdomain,
                        "port" => $allocation->port,
                        "target" => $allocation->ip_alias,
                    ]) !== true) {
                        Log::error('failed to add subdomain record', [
                            'body' => $dns->getBody(),
                            'zone' => $zoneID,
                            "service" => $protocol,
                            "proto" => "_" . $type,
                            "name" => $subdomain->subdomain,
                            "port" => $allocation->port,
                            "target" => $allocation->ip_alias,
                        ]);

                        throw new DisplayException('Failed to create subdomain record.');
                    }
                } catch (\Exception $e) {
                    Log::error('exception occurred creating srv subdomain', [
                        'body' => $dns->getBody(),
                        'zone' => $zoneID,
                        "service" => $protocol,
                        "proto" => "_" . $type,
                        "name" => $subdomain->subdomain,
                        "port" => $allocation->port,
                        "target" => $allocation->ip_alias,
                        'ex' => $e
                    ]);

                    throw new DisplayException('Failed to create subdomain.');
                }
            }
        }

        return true;
    }
}

?>
