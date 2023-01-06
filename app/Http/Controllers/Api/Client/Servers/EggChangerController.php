<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Iceline\ServerTransferService;
use Pterodactyl\Services\Servers\AutoAllocationService;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\EggChangerRequest;
use xPaw\SourceQuery\Exception\InvalidArgumentException;
use xPaw\SourceQuery\Exception\InvalidPacketException;
use xPaw\SourceQuery\Exception\SocketException;

class EggChangerController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\StartupModificationService
     */
    protected $startupModificationService;

    /**
     * @var \Pterodactyl\Services\Servers\ReinstallServerService
     */
    protected $reinstallServerService;

    /**
     * @var \Pterodactyl\Services\Servers\AutoAllocationService
     */
    protected $autoAllocationService;

    /**
     * @var BuildModificationService
     */
    protected $buildModificationService;

    /**
     * @param StartupModificationService $startupModificationService
     * @param ReinstallServerService $reinstallServerService
     * @param AutoAllocationService $autoAllocationService
     * @param BuildModificationService $buildModificationService
     */
    public function __construct(
        StartupModificationService $startupModificationService,
        ReinstallServerService     $reinstallServerService,
        AutoAllocationService      $autoAllocationService,
        BuildModificationService   $buildModificationService
    )
    {
        parent::__construct();

        $this->startupModificationService = $startupModificationService;
        $this->reinstallServerService = $reinstallServerService;
        $this->autoAllocationService = $autoAllocationService;
        $this->buildModificationService = $buildModificationService;
    }

    /**
     * @param EggChangerRequest $request
     * @param Server $server
     * @return array
     */
    public function index(EggChangerRequest $request, Server $server)
    {
        $selectable_eggs = [];

        foreach (unserialize($server->available_eggs) as $item) {
            $egg = DB::table('eggs')->select(['id', 'name', 'thumbnail'])->where('id', '=', $item)->get();

            if (count($egg) > 0) {
                array_push($selectable_eggs, $egg[0]);
            }
        }

        return [
            'success' => true,
            'data' => [
                'eggs' => $selectable_eggs,
                'currentEggId' => $server->egg_id,
            ],
        ];
    }

    /**
     * @param EggChangerRequest $request
     * @param Server $server
     * @return array|JsonResponse
     * @throws DisplayException
     */
    public function change(EggChangerRequest $request, Server $server)
    {
        $this->validate($request, [
            'eggId' => 'required|integer',
        ]);

        $reinstallServer = true; // (bool) $request->input('reinstallServer', false);

        Log::info('Checking if egg ID is valid');
        $egg = DB::table('eggs')->where('id', '=', (int) $request->input('eggId', 0))->get();
        if (count($egg) < 1) {
            Log::error('Egg not found.');

            return new JsonResponse(['error' => 'Egg not found.'], 400);
        }

        Log::info('Retrieving available server eggs');
        $available = DB::table('available_eggs')->where('egg_id', '=', (int) $request->input('eggId', 0))->get();
        if (count($available) < 1) {
            Log::error('This egg isn\'t available to this server.');

            return new JsonResponse(['error' => 'This egg isn\'t available to this server.'], 400);
        }

        Log::info('Checking is egg is valid');
        $available_eggs = unserialize($server->available_eggs);
        if (!in_array((int) $request->input('eggId', 0), $available_eggs)) {
            Log::error('This egg isn\'t available to this server.');

            return new JsonResponse(['error' => 'This egg isn\'t available to this server.'], 400);
        }

        $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);

        try {
            Log::info('Running server modifications for egg change');
            $this->startupModificationService->handle($server, [
                'nest_id' => $egg[0]->nest_id,
                'egg_id' => $egg[0]->id,
                'docker_image' => json_decode($egg[0]->docker_images, true)[array_keys(json_decode($egg[0]->docker_images, true))[0]],
                'startup' => $egg[0]->startup,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to change the egg. Please try again...');

            return new JsonResponse(['error' => 'Failed to change the egg. Please try again...' . $e->getMessage()], 500);
        }

        // Process the port change if needed
        if (!is_null($egg[0]->port_range)) {
            $allocation = $server->allocation;
            $newRange = explode('-', $egg[0]->port_range);

            if ($allocation->port < $newRange[0] || $allocation->port > $newRange[1]) {
                // Get the new primary allocation
                $newAllocation = DB::table('allocations')
                    ->where('node_id', '=', $server->node_id)
                    ->where('ip', '=', $allocation->ip)
                    ->whereNull('server_id')
                    ->where('port', '>=', $newRange[0])
                    ->where('port', '<=', $newRange[1])
                    ->inRandomOrder()
                    ->first();

                if (!$newAllocation) {
                    throw new DisplayException('Egg changed, but failed to change the primary allocation because there are no available port.');
                }

                // Get the current environment variables
                $variables = [];
                foreach (Server::find($server->id)->variables()->get() as $variable) {
                    //$variables[$variable->env_variable] = $variable->server_value;
                    $variables[] = (object) [
                        'key' => $variable->env_variable,
                        'value' => $variable->server_value ?? $variable->default_value,
                    ];
                }

                // Generate the new additional allocations
                $server->egg_id = $egg[0]->id;
                $server->allocation_id = $newAllocation->id;
                $autoAllocation = $this->autoAllocationService->handle($server, $variables);

                $newIds = [];
                foreach ($autoAllocation['allocations'] as $new) {
                    if (isset($new[0])) {
                        $newIds[] = $new[0];
                    }
                }
                $newIds[] = $newAllocation->id;

                $variablesToStartup = [];
                foreach ($autoAllocation['eggs'] as $variable) {
                    $variablesToStartup[$variable->key] = strval($variable->value);
                }

                // Modify the allocations
                try {
                    $this->buildModificationService->handle($server, [
                        'database_limit' => $server->database_limit,
                        'allocation_limit' => $server->allocation_limit,
                        'backup_limit' => $server->backup_limit,
                        'backup_limit_by_size' => $server->backup_limit_by_size,
                        'allocation_id' => $newAllocation->id,
                        'add_allocations' => $newIds,
                        'remove_allocations' => $server->allocations()->pluck('id')->all(),
                    ]);
                } catch (DisplayException|\Throwable $e) {
                    throw new DisplayException('Egg changed, but failed to change the primary allocation.' . $e->getMessage());
                }

                // Modify the environment variables
                try {
                    $this->startupModificationService->handle($server, [
                        'environment' => $variablesToStartup,
                    ]);
                } catch (\Throwable $e) {
                    throw new DisplayException('Failed to modify the egg variables.');
                }
            }
        }

        if ($reinstallServer) {
            try {
                $this->reinstallServerService->handle($server);
            } catch (\Throwable $e) {
                Log::error('Egg was changed, but failed to trigger server reinstall.');

                return new JsonResponse([
                    'error' => 'Egg was changed, but failed to trigger server reinstall.'
                ], 500);
            }
        }

        return [
            'success' => true,
            'data' => [],
        ];
    }
}
