<?php

namespace Pterodactyl\Services\Databases;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException;

class DeployServerDatabaseService
{
    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * ServerDatabaseCreationService constructor.
     *
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService $managementService
     */
    public function __construct(DatabaseManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    /**
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function handle(Server $server, array $data): Database
    {
        Assert::notEmpty($data['database'] ?? null);
        Assert::notEmpty($data['remote'] ?? null);

        $database_host_id = null;

        $hosts = DatabaseHost::query()->get()->toBase();
        if ($hosts->isEmpty()) {
            throw new NoSuitableDatabaseHostException();
        } else {
            $nodeHosts = $hosts->where('node_id', $server->node_id)->toBase();

            if ($nodeHosts->isEmpty()) {
                $locationHosts = $hosts->where('location_id', $server->location->id)->toBase();

                if (!$locationHosts->isEmpty()) {
                    $database_host_id = $locationHosts->random()->id;
                }
            } else {
                $database_host_id = $nodeHosts->random()->id;
            }
        }

        if ($database_host_id == null) {
            if (config('pterodactyl.client_features.databases.allow_random')) {
                $database_host_id = $hosts->random()->id;
            } else {
                throw new NoSuitableDatabaseHostException();
            }
        }

        return $this->managementService->create($server, [
            'database_host_id' => $database_host_id,
            'database' => DatabaseManagementService::generateUniqueDatabaseName($data['database'], $server->id),
            'remote' => $data['remote'],
        ]);
    }
}
