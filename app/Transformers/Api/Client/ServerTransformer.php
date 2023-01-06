<?php

namespace Pterodactyl\Transformers\Api\Client;

use Exception;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Iceline\FiveMLicense;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Permission;
use Illuminate\Container\Container;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Servers\StartupCommandService;

class ServerTransformer extends BaseClientTransformer
{
    /**
     * @var string[]
     */
    protected array $defaultIncludes = ['allocations', 'variables'];

    protected array $availableIncludes = ['egg', 'subusers'];

    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * Transform a server model into a representation that can be returned
     * to a client.
     */
    public function transform(Server $server): array
    {
        /** @var \Pterodactyl\Services\Servers\StartupCommandService $service */
        $service = Container::getInstance()->make(StartupCommandService::class);

        $user = $this->request->user();

        // Load CFX url for the server
        $cfxUrl = null;
        if (!$server->suspended) {
            // Acquire the CFX URL
            if (str_contains($server->nest->name, "FiveM") || str_contains($server->nest->name, "RedM") || str_contains($server->egg->name, "FiveM")) {
                // Check that the server has a license key
                // @var \Pterodactyl\Models\EggVariable $variable
                $variable = $server->variables()
                    ->where('env_variable', 'FIVEM_LICENSE')
                    ->orWhere('env_variable', 'REDM_LICENSE')->first();
                if (!empty($variable->server_value)) {
                    /** @var FiveMLicense $license */
                    $license = FiveMLicense::query()
                        ->where('server_id', $server->id)
                        ->where('key', $variable->server_value)->first();
                    if (!is_null($license)) {
                        $cfxUrl = $license->cfx_url;
                    } else {
                        try {
                            $url = "http://" . $server->allocation->ip . ":" . $server->allocation->port;

                            // If it's an internal IP then the allocation is using an alias
                            if (str_starts_with($server->allocation->ip, "192.")) {
                                $url = "http://" . $server->allocation->alias . ":" . $server->allocation->port;
                            }

                            Log::info('requesting cfx url', [
                                'server' => $server->id,
                                'url' => $url
                            ]);

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_VERBOSE, 0);
                            curl_setopt($ch, CURLOPT_FAILONERROR, false);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second total operation timeout
                            $response = curl_exec($ch);
                            $responseUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                            if (!str_starts_with($responseUrl, $url) && curl_error($ch) == '') {
                                $cfxUrl = $responseUrl;

                                // Cache the url
                                $license = new FiveMLicense;
                                $license->server_id = $server->id;
                                $license->cfx_url = $cfxUrl;
                                $license->key = $variable->server_value;

                                if (!$license->save()) {
                                    Log::info('error caching cfx url', [
                                        'server' => $server->id
                                    ]);
                                }
                            } else if (curl_error($ch) != '') {
                                Log::error('curl error for cfx url', [
                                    'server' => $server->id,
                                    'err' => curl_error($ch),
                                    'no_err' => curl_error($ch) == '',
                                    'responseUrl' => $responseUrl,
                                    'url' => $url,
                                    'does_start' => !str_starts_with($responseUrl, $url)
                                ]);
                            } else {
                                Log::error('got incorrectly formatted cfx url', [
                                    'server' => $server->id,
                                    'url' => $url
                                ]);
                            }
                        } catch (Exception $ex) {
                            Log::error('error getting cfx url', [
                                'server' => $server->id,
                                'ex' => $ex
                            ]);
                        }
                    }
                } else {
                    Log::error('no fivem license found for cfx url retrieval', [
                        'server' => $server->id
                    ]);
                }
            }
        }

        return [
            'server_owner' => $user->id === $server->owner_id,
            'identifier' => $server->uuidShort,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'sftp_details' => [
                'ip' => $server->node->fqdn,
                'port' => $server->node->daemonSFTP,
            ],
            'description' => $server->description,
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
                'threads' => $server->threads,
                'oom_disabled' => $server->oom_disabled,
            ],
            'invocation' => $service->handle($server, !$user->can(Permission::ACTION_STARTUP_READ, $server)),
            'docker_image' => $server->image,
            'egg_features' => $server->egg->inherit_features,
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
                'backup_limit_by_size' => $server->backup_limit_by_size,
            ],
            'status' => $server->status,
            // This field is deprecated, please use "status".
            'is_suspended' => $server->isSuspended(),
            // This field is deprecated, please use "status".
            'is_installing' => !$server->isInstalled(),
            'is_transferring' => !is_null($server->transfer),
            'cfx_url' => $cfxUrl,
            'alerts' => \Illuminate\Support\Facades\DB::table('alerts')->where('created_at', '<', \Carbon\Carbon::now())->where('expire_at', '>', \Carbon\Carbon::now())->orderBy('created_at', 'ASC')->get()->filter(function ($item) use ($server) {
                return in_array($server->node_id, json_decode($item->node_ids));
            }),
        ];
    }

    /**
     * Returns the allocations associated with this server.
     *
     * @return \League\Fractal\Resource\Collection
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeAllocations(Server $server)
    {
        $transformer = $this->makeTransformer(AllocationTransformer::class);

        $user = $this->request->user();
        // While we include this permission, we do need to actually handle it slightly different here
        // for the purpose of keeping things functionally working. If the user doesn't have read permissions
        // for the allocations we'll only return the primary server allocation, and any notes associated
        // with it will be hidden.
        //
        // This allows us to avoid too much permission regression, without also hiding information that
        // is generally needed for the frontend to make sense when browsing or searching results.
        if (!$user->can(Permission::ACTION_ALLOCATION_READ, $server)) {
            $primary = clone $server->allocation;
            $primary->notes = null;

            return $this->collection([$primary], $transformer, Allocation::RESOURCE_NAME);
        }

        return $this->collection($server->allocations, $transformer, Allocation::RESOURCE_NAME);
    }

    /**
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeVariables(Server $server)
    {
        if (!$this->request->user()->can(Permission::ACTION_STARTUP_READ, $server)) {
            return $this->null();
        }

        return $this->collection(
            $server->variables->where('user_viewable', true),
            $this->makeTransformer(EggVariableTransformer::class),
            EggVariable::RESOURCE_NAME
        );
    }

    /**
     * Returns the egg associated with this server.
     *
     * @return \League\Fractal\Resource\Item
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeEgg(Server $server)
    {
        return $this->item($server->egg, $this->makeTransformer(EggTransformer::class), Egg::RESOURCE_NAME);
    }

    /**
     * Returns the subusers associated with this server.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server)
    {
        if (!$this->request->user()->can(Permission::ACTION_USER_READ, $server)) {
            return $this->null();
        }

        return $this->collection($server->subusers, $this->makeTransformer(SubuserTransformer::class), Subuser::RESOURCE_NAME);
    }
}
