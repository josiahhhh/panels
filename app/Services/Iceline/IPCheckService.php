<?php

namespace Pterodactyl\Services\Iceline;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;

class IPCheckService {
    /**
     * LocationCreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $repository
     */
    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    function remove_duplicate_allocations( $allocations ) {
        $ips = array_map( function( $allocation ) {
            return $allocation->ip;
        }, $allocations );

        $unique_ips = array_unique( $ips );

        return array_values( array_intersect_key( $allocations, $unique_ips ) );
    }

    /**
     * Check IP addresses
     *
     * @param int|null $port
     * @return array
     *
     * @throws DisplayException
     */
    public function handle(int $port = null) {
        $query = DB::table('allocations');
        if (!is_null($port)) {
            $query = $query->where('port', 'LIKE', $port . '%');
        }

        // Retrieve allocations
        $allocations = $query->get();
        if (count($allocations) < 1) {
            throw new DisplayException('No allocations found to process');
        }
        $allocations = $this->remove_duplicate_allocations($allocations->toArray());

        $blacklistedAllocations = [];

        // Loop over allocations and check
        // if they've been blacklisted.
        /** @var Allocation $allocation */
        foreach ($allocations as $allocation) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://runtime.fivem.net/blacklist/' . $allocation->ip);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_exec($curl);

            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Check the return status
            if ($http_status == 200) {
                // Add the blacklisted allocation to the list
                $blacklistedAllocations[] = $allocation;
            }
        }

        // Remove allocations with duplicated IP addresses so
        // we're not accidentally processing multiple IPs.
        $blacklistedAllocations = $this->remove_duplicate_allocations($blacklistedAllocations);

        return $blacklistedAllocations;
    }
}
