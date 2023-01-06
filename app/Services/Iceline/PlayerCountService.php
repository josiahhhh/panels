<?php

namespace Pterodactyl\Services\Iceline;

require __DIR__ . '/PlayersController/MinecraftPing.php';
require __DIR__ . '/PlayersController/MinecraftPingException.php';
require __DIR__ . '/PlayersController/MinecraftQuery.php';
require __DIR__ . '/PlayersController/MinecraftQueryException.php';

require __DIR__ . '/PlayersController/SourceQuery/bootstrap.php';


use Exception;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;
use xPaw\SourceQuery\SourceQuery;

class PlayerCountService {
    /**
     * LocationCreationService constructor.
     *
     */
    public function __construct() { }

    function file_contents($path) {
        $ctx = stream_context_create(array('http'=>
            array(
                'timeout' => 5,  // 5 seconds
            )
        ));

        $str = @file_get_contents($path, false, $ctx);
        if ($str === FALSE) {
            throw new Exception("Cannot access '$path' to read contents.");
        } else {
            return $str;
        }
    }

    /**
     * Check IP addresses
     *
     * @param Server $server
     * @return object
     *
     * @throws \xPaw\SourceQuery\Exception\InvalidArgumentException
     * @throws \xPaw\SourceQuery\Exception\InvalidPacketException
     * @throws \xPaw\SourceQuery\Exception\SocketException
     */
    public function handle(Server $server) {
        $playerCount = 0;
        $maxPlayerCount = 0;
        $message = null;
        $players = [];
        $error = false;

        $connectionAddress = $server->allocation->ip;

        // Check to account for dev-panel IPs being 0.0.0.0 with alias
        if (($connectionAddress == "0.0.0.0" || $connectionAddress == "127.0.0.1") && $server->allocation->has_alias) {
            $connectionAddress = $server->allocation->alias;
        }

        if (!$server->suspended) {
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
                            $error = true;

                            break;
                        } else {
                            $maxPlayerCount = $result["players"]["max"];
                            $playerCount = $result["players"]["online"];
                            if (array_key_exists("sample", $result["players"])) {
                                $players = $result["players"]["sample"];
                            }
                        }
                    } catch (MinecraftPingException | Exception $e) {
                        $maxPlayerCount = 0;
                        $playerCount = 0;

                        $message = 'Failed to ping server: ' . $e->getMessage();
                        $error = true;
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
                        $message = 'Failed to retrieve player count for rust server: ' . $e->getMessage();
                        $error = true;
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
                        $message = 'Failed to retrieve player count for source engine server: ' . $e->getMessage();
                        $error = true;
                        break;
                    } finally {
                        $query->Disconnect();
                    }
                    break;
                case "FiveM":
                    try {
                        $serverInfo = json_decode($this->file_contents("http://" . $connectionAddress . ":" . $server->allocation->port . "/info.json"), true);
                        if ($serverInfo) {
                            $playerInfo = json_decode($this->file_contents("http://" . $connectionAddress . ":" . $server->allocation->port . "/players.json"), true);

                            $maxPlayerCount = (int)($serverInfo["vars"]["sv_maxClients"]);
                            $playerCount = count($playerInfo);

                            foreach ($playerInfo as $player) {
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

                                $players[] = [
                                    'id' => $player['id'],
                                    'name' => $player['name'],
                                    'metadata' => [
                                        'identifier' => $identifier,
                                        'ping' => $player['ping'] . ' ms'
                                    ]
                                ];
                            }
                        } else {
                            $maxPlayerCount = 0;
                            $playerCount = 0;
                            $message = 'Failed to retrieve player count for FiveM server';
                            $error = true;
                            break;
                        }
                    } catch (Exception $e) {
                        $maxPlayerCount = 0;
                        $playerCount = 0;
                        $message = 'Failed to retrieve player count for FiveM server: ' . $e->getMessage();
                        $error = true;
                        break;
                    }

                    break;
                default:
                    $message = 'Player count not supported for nest ' . $server->nest->name;
                    $error = true;
            }
        }

        return (object)[
            'max' => $maxPlayerCount,
            'current' => $playerCount,
            'players' => $players,
            'message' => $message,
            'error' => $error
        ];
    }
}
