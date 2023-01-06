<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

require __DIR__ . '/PlayersController/MinecraftPing.php';
require __DIR__ . '/PlayersController/MinecraftPingException.php';
require __DIR__ . '/PlayersController/MinecraftQuery.php';
require __DIR__ . '/PlayersController/MinecraftQueryException.php';
require __DIR__ . '/PlayersController/SourceQuery/bootstrap.php';

use Exception;
use Pterodactyl\Exceptions\DisplayException;
use xPaw\MinecraftPing;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use xPaw\MinecraftPingException;
use xPaw\SourceQuery\SourceQuery;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class PlayersController extends ClientApiController
{
    /**
     * @var DaemonFileRepository
     */
    protected $fileRepository;

    /**
     * @param DaemonFileRepository $fileRepository
     */
    public function __construct(DaemonFileRepository $fileRepository)
    {
        parent::__construct();

        $this->fileRepository = $fileRepository;
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

                foreach ($playerResponse as $player) {
                    $identifiers = $player['identifiers'];
                    $identifier = '';
                    foreach ($identifiers as $id) {
                        if (substr($id, 0, strlen('steam')) === 'steam') {
                            $identifier = $id;
                            break;
                        } else if (substr($id, 0, strlen('license')) === 'license') {
                            $identifier = $id;
                            break;
                        }
                    }

                    $playerInfo['players'][] = [
                        'id' => $player['id'],
                        'name' => $player['name'],
                        'metadata' => [
                            'identifier' => $identifier,
                            'ping' => $player['ping'] . ' ms'
                        ]
                    ];
                }
            } else {
                return null;
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $playerInfo;
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return array
     */
    public function count(Request $request, Server $server)
    {
        $playerCount = 0;
        $maxPlayerCount = 0;
        $message = null;
        $players = [];

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
                    /* Log::error('failed to get player count for fivem server from primary', [
                        'server' => $server->id,
                        'ex' => $e
                    ]); */
                    $playerInfo = null;
                }

                if (is_null($playerInfo)) {
                    $maxPlayerCount = 0;
                    $playerCount = 0;
                    $message = 'Failed to retrieve player count for fivem server, no alias available to check';
                    goto returnPlayers;
                }

                $maxPlayerCount = $playerInfo['maxPlayerCount'];
                $playerCount = $playerInfo['playerCount'];
                $players = $playerInfo['players'];

                goto returnPlayers;
            }

            switch ($server->nest->name) {
                case "Minecraft":
                    $query = null;

                    try {
                        $query = new MinecraftPing(
                            $connectionAddress,
                            $server->allocation->port);

                        $result = $query->Query();
                        if (!$result) {
                            $message = 'Failed to open connection to minecraft server';

                            break;
                        } else {
                            $maxPlayerCount = $result["players"]["max"];
                            $playerCount = $result["players"]["online"];
                            if (array_key_exists("sample", $result["players"])) {
                                $players = $result["players"]["sample"];
                            }

                            // op list
                            $ops = $this->readJsonFile($server, '/ops.json');

                            // whitelist
                            $whitelist = $this->readJsonFile($server, '/whitelist.json');

                            // update player list
                            foreach ($players as $key => $player) {
                                $players[$key]['isOp'] = array_search($player['name'], array_column($ops, 'name')) !== false;
                                $players[$key]['inWhitelist'] = array_search($player['name'], array_column($whitelist, 'name')) !== false;
                            }
                        }
                    } catch (MinecraftPingException|Exception $e) {
                        $maxPlayerCount = 0;
                        $playerCount = 0;

                        $message = 'Failed to ping server: ' . $e->getMessage();
                    } finally {
                        if ($query) {
                            $query->Close();
                        }
                    }

                    // TODO: this can be enabled to support player lists on query-enabled servers
                    // Attempt to use query to retrieve player info if it's enabled in the server.properties
                    /* $query = new MinecraftQuery();
                    try {
                        $query->Connect( $connectionAddress, $server->allocation->port );

                        $playersList = $query->GetPlayers();
                        if ($playersList !== false) {
                            foreach ($playersList as $player) {
                                $players[] = [
                                    'name' => $player,
                                    'metadata' => $player
                                ];
                            }
                        }
                    } catch( MinecraftQueryException $e ) {
                        Log::warning("error retrieving player list for minecraft server (query is probably disabled and this can be ignored)", [
                            'ex' => $e,
                        ]);
                    } */

                    break;
                case "Source Engine":
                    $query = new SourceQuery();
                    try {
                        $query->Connect($connectionAddress, $server->allocation->port, 1, \xPaw\SourceQuery\SourceQuery::SOURCE);
                        $srvinfo = $query->GetInfo();
                        $maxPlayerCount = $srvinfo['MaxPlayers'];
                        $playerCount = $srvinfo['Players'];

                        // Query the list of players
                        $playersList = $query->GetPlayers();
                        foreach ($playersList as $player) {
                            $players[] = [
                                'name' => $player['Name'],
                                'metadata' => [
                                    'Time' => $player['TimeF']
                                ]
                            ];
                        }
                    } catch (Exception $e) {
                        $maxPlayerCount = 0;
                        $playerCount = 0;
                        $message = 'Failed to retrieve player count for rust server';
                        break;
                    } finally {
                        $query->Disconnect();
                    }
                    break;
                case "Rust":
                    $query = new SourceQuery();
                    try {
                        $query->Connect($connectionAddress, $server->allocation->port, 1, \xPaw\SourceQuery\SourceQuery::SOURCE);
                        $srvinfo = $query->GetInfo();
                        $maxPlayerCount = $srvinfo['MaxPlayers'];
                        $playerCount = $srvinfo['Players'];

                        // Query the list of players
                        $playersList = $query->GetPlayers();
                        foreach ($playersList as $player) {
                            $players[] = [
                                'name' => $player['Name'],
                                'metadata' => [
                                    'Time' => $player['TimeF']
                                ]
                            ];
                        }
                    } catch (Exception $e) {
                        $maxPlayerCount = 0;
                        $playerCount = 0;
                        $message = 'Failed to retrieve player count for source engine server';
                        break;
                    } finally {
                        $query->Disconnect();
                    }
                    break;
                default:
                    $message = 'Player count not supported for nest ' . $server->nest->name;
            }
        }

        returnPlayers:

        return [
            'success' => true,
            'data' => [
                'max' => $maxPlayerCount,
                'current' => $playerCount,
                'players' => $players,
                'message' => $message,
            ]
        ];
    }

    /**
     * @param Server $server
     * @param $file
     * @return array|mixed
     */
    public function readJsonFile(Server $server, $file)
    {
        try {
            $content = $this->fileRepository->setServer($server)->getContent($file);
        } catch (DaemonConnectionException|FileSizeTooLargeException $e) {
            return [];
        }

        if (!is_array(json_decode($content, true))) {
            return [];
        }

        return json_decode($content, true);
    }
}
