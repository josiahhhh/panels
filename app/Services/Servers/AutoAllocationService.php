<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Facades\DB;

class AutoAllocationService
{
    /**
     * @var array
     */
    private $new_ports = [];

    /**
     * @param $server
     * @param $eggVariables
     * @return array|false
     */
    public function handle($server, $eggVariables)
    {
        $allocation = DB::table('auto_allocation_adder')->where('egg_id', '=', $server->egg_id)->get();
        if (count($allocation) > 0) {
            $config = unserialize($allocation[0]->allocations);
            $default_allocation = DB::table('allocations')->where('id', '=', $server->allocation_id)->get();

            $next_cache = 1;
            $new_allocation_ids = [];

            foreach ($config as $item) {
                $env = '';
                if (isset($item['environment_name']) && !empty($item['environment_name'])) {
                    $env = $item['environment_name'];
                }

                // Plus X
                if ($item['type'] == '+') {
                    $this->new_ports[] = [
                        'port' => intval($default_allocation[0]->port) + intval($item['port']),
                        'variable' => $env,
                    ];
                }

                // Minus X
                if ($item['type'] == '-') {
                    $this->new_ports[] = [
                        'port' => intval($default_allocation[0]->port) - intval($item['port']),
                        'variable' => $env,
                    ];
                }

                // Next port
                if ($item['type'] == 'next') {
                    $new_port = $this->calculateNextAllocation($server->node_id, intval($default_allocation[0]->port) + $next_cache, $default_allocation[0]->port);

                    if ($new_port != false) {
                        $next_cache = $next_cache + 1;
                        $this->new_ports[] = [
                            'port' => $new_port,
                            'variable' => $env,
                        ];
                    }
                }

                // Random port in range
                if ($item['type'] == 'random') {
                    $range = explode('-', $item['port']);
                    if (count($range) == 2) {
                        if (!empty($range[0]) && !empty($range[1])) {
                            $available = DB::table('allocations')
                                ->where('port', '>=', intval($range[0]))
                                ->where('port', '<=', intval($range[1]))
                                ->where('node_id', '=', $server->node_id)
                                ->whereNull('server_id')
                                ->get();

                            $available = json_decode(json_encode($available), true);

                            if (count($available) > 0) {
                                $this->new_ports[] = [
                                    'port' => intval($available[array_rand($available)]['port']),
                                    'variable' => $env,
                                ];
                            }
                        }
                    }
                }
            }

            foreach ($this->new_ports as $new_port) {
                $new_allocation = DB::table('allocations')->where('port', '=', $new_port['port'])->where('node_id', '=', $server->node_id)->whereNull('server_id')->get();
                if (count($new_allocation) > 0) {
                    $new_allocation_ids[] = [$new_allocation[0]->id];

                    if (!empty($new_port['variable'])) {
                        foreach ($eggVariables as $key => $eggVariable) {
                            if ($eggVariable->key == $new_port['variable']) {
                                $eggVariables[$key]->value = $new_allocation[0]->port;
                            }
                        }
                    }
                }
            }

            return [
                'allocations' => $new_allocation_ids,
                'eggs' => $eggVariables,
            ];
        } else {
            return false;
        }
    }

    /**
     * @param $node_id
     * @param $new_port
     * @param $default_port
     * @return mixed
     */
    private function calculateNextAllocation($node_id, $new_port, $default_port)
    {
        $available_allocations = DB::table('allocations')->where('node_id', '=', $node_id)->where('port', '>', $default_port)->whereNull('server_id')->get();
        foreach ($available_allocations as $key => $available_allocation) {
            if (in_array($available_allocation->port, $this->new_ports)) {
                unset($available_allocations[$key]);
            }
        }

        if (count($available_allocations) < 1) {
            return false;
        }

        $new_allocation = DB::table('allocations')->where('port', '=', $new_port)->where('node_id', '=', $node_id)->whereNull('server_id')->get();
        if (count($new_allocation) > 0) {
            return $new_allocation[0]->port;
        } else {
            return $this->calculateNextAllocation($node_id, $new_port + 1, $new_port + 1);
        }
    }
}

?>
