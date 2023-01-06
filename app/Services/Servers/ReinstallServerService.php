<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class ReinstallServerService
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * ReinstallService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->connection = $connection;
    }

    /**
     * Reinstall a server on the remote daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool $purgeFiles
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Throwable
     */
    public function handle(Server $server, bool $purgeFiles = false)
    {
        return $this->connection->transaction(function () use ($purgeFiles, $server) {
            $server->fill(['status' => Server::STATUS_INSTALLING])->save();

            $this->daemonServerRepository->setServer($server)->reinstall($purgeFiles);

            if ($purgeFiles == true) {
                // Remove installed mods on server reinstall
                DB::table('mod_manager_installed_mods')->where('server_id', '=', $server->id)->delete();
            }

            return $server->refresh();
        });
    }
}
