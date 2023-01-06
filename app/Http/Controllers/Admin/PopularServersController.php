<?php

namespace Pterodactyl\Http\Controllers\Admin;

require __DIR__ . '/../Api/Client/Servers/Iceline/PlayersController/MinecraftPing.php';
require __DIR__ . '/../Api/Client/Servers/Iceline/PlayersController/MinecraftPingException.php';
require __DIR__ . '/../Api/Client/Servers/Iceline/PlayersController/MinecraftQuery.php';
require __DIR__ . '/../Api/Client/Servers/Iceline/PlayersController/MinecraftQueryException.php';
require __DIR__ . '/../Api/Client/Servers/Iceline/PlayersController/SourceQuery/bootstrap.php';

use Exception;
use xPaw\MinecraftPing;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use xPaw\MinecraftPingException;
use xPaw\SourceQuery\SourceQuery;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Http\Controllers\Controller;

class PopularServersController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $playersToShow = [];
        $serversToShow = Server::whereNull('status')
            ->select(['servers.id', 'servers.name', 'servers.owner_id', 'servers.allocation_id', 'servers.egg_id', 'servers.nest_id', 'users.name_first as firstname', 'users.name_last as lastname'])
            ->leftJoin('users', 'users.id', '=', 'servers.owner_id')
            ->paginate(15);

        foreach ($serversToShow as $server) {
            if (Cache::has('players::' . $server->id)) {
                $players = Cache::get('players::' . $server->id);
            } else {
                $players = $this->getPlayers($server);

                if ($players['max'] != 0) {
                    Cache::put('players::' . $server->id, $players, 300);
                }
            }

            $playersToShow[$server->id] = $players;
        }

        return view('admin.players', [
            'servers' => $serversToShow,
            'players' => $playersToShow,
        ]);
    }

    /**
     * @param string $address
     * @param Server $server
     * @return array|null
     */
    function getFiveMPlayers(string $address, Server $server)
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 seconds
            ],
        ]);

        $playerInfo = [
            'players' => [],
            'maxPlayerCount' => 0,
            'playerCount' => 0
        ];

        try {
            // Log::info('attempting to retrieve server info from ' . "http://" . $address . "/info.json", [ 'server' => $server->id ]);
            $serverInfo = json_decode(file_get_contents("http://" . $address . "/info.json", false, $ctx), true);
            if ($serverInfo) {
                // Log::info('attempting to retrieve players from ' . "http://" . $address . "/players.json", [ 'server' => $server->id ]);
                $playerResponse = json_decode(file_get_contents("http://" . $address . "/players.json", false, $ctx), true);

                $playerInfo['maxPlayerCount'] = (int) ($serverInfo["vars"]["sv_maxClients"]);
                $playerInfo['playerCount'] = count($playerResponse);
            } else {
                return null;
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $playerInfo;
    }

    /**
     * @param $server
     * @return array
     */
    public function getPlayers($server)
    {
        $playerCount = 0;
        $maxPlayerCount = 0;
        $connectionAddress = $server->allocation->ip;

        // Check to account for dev-panel IPs being 0.0.0.0 with alias
        if (($connectionAddress == "0.0.0.0" || $connectionAddress == "127.0.0.1") && $server->allocation->has_alias) {
            $connectionAddress = $server->allocation->alias;
        }

        if (!$server->suspended) {
            if ($server->nest->name == "FiveM" || $server->egg->name == "FXServer Proxy" || $server->nest->name == "RedM" || str_contains($server->egg->name, 'FiveM')) {
                $playerInfo = null;

                $address = $connectionAddress . ":" . $server->allocation->port;
                if (str_starts_with($connectionAddress, "192")) {
                    $address = $server->allocation->alias . ":" . $server->allocation->port;
                }

                // $address = '136.175.222.3:30120';

                try {
                    $playerInfo = $this->getFiveMPlayers($address, $server);
                } catch (Exception $e) {
                    $playerInfo = null;
                }

                if (is_null($playerInfo)) {
                    $maxPlayerCount = 0;
                    $playerCount = 0;
                    goto returnPlayers;
                }

                $maxPlayerCount = $playerInfo['maxPlayerCount'];
                $playerCount = $playerInfo['playerCount'];

                goto returnPlayers;
            }

            switch ($server->nest->name) {
                case "Minecraft":
                    $query = null;

                    try {
                        $query = new MinecraftPing($connectionAddress, $server->allocation->port);

                        $result = $query->Query();
                        if (!$result) {
                            break;
                        } else {
                            $maxPlayerCount = $result["players"]["max"];
                            $playerCount = $result["players"]["online"];
                        }
                    } catch (MinecraftPingException|Exception $e) {
                        $maxPlayerCount = 0;
                        $playerCount = 0;
                    } finally {
                        if ($query) {
                            $query->Close();
                        }
                    }
                    break;
                case "Rust":
                case "Source Engine":
                    $query = new SourceQuery();
                    try {
                        $query->Connect($connectionAddress, $server->allocation->port, 1, \xPaw\SourceQuery\SourceQuery::SOURCE);
                        $srvinfo = $query->GetInfo();
                        $maxPlayerCount = $srvinfo['MaxPlayers'];
                        $playerCount = $srvinfo['Players'];
                    } catch (Exception $e) {
                        $maxPlayerCount = 0;
                        $playerCount = 0;
                        break;
                    } finally {
                        $query->Disconnect();
                    }
                    break;
                default:
                    break;
            }
        }

        returnPlayers:

        return [
            'online' => $playerCount,
            'max' => $maxPlayerCount,
        ];
    }
}
