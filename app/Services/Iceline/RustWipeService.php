<?php

namespace Pterodactyl\Services\Iceline;

use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;

class RustWipeService
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
        $this->fileRepository = $fileRepository;
    }

    /**
     * @param Server $server
     * @param $wipe
     * @return void
     * @throws DisplayException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function handle(Server $server, $wipe)
    {
        $serverFiles = [];
        $fileRepository = $this->fileRepository->setServer($server);

        if ($wipe == 'blueprint') {
            try {
                $files = $fileRepository->getDirectory('/server/rust');

                // Find all the blueprint files in the directory
                foreach ($files as $file) {
                    $basename = pathinfo($file['name'], PATHINFO_BASENAME);
                    if (preg_match('/.*?\.blueprints\..*?\.db/m', $basename)) {
                        $serverFiles[] = $basename;
                    }
                }
            } catch (\Exception $ex) {
                Log::warning('failed to get list of files for rust blueprint wipe', [
                    'ex' => $ex
                ]);

                throw new DisplayException('Failed to get list of files to wipe - does the /server/rust directory exist?');
            }
        } else if ($wipe == 'map') {
            try {
                $files = $fileRepository->getDirectory('/server/rust');

                // Find all the map files in the directory
                foreach ($files as $file) {
                    $basename = pathinfo($file['name'], PATHINFO_BASENAME);
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if ($ext == 'sav' || $ext == 'map' || preg_match('/.*?\.sav.\d/m', $basename)) {
                        $serverFiles[] = $basename;
                    }
                }
            } catch (\Exception $ex) {
                Log::warning('failed to get list of files for rust map wipe', [
                    'ex' => $ex
                ]);

                throw new DisplayException('Failed to get list of files to wipe - does the /server/rust directory exist?');
            }
        } else if ($wipe == 'full') {
            try {
                $files = $fileRepository->getDirectory('/server/rust');

                // Get all the files in the server directory
                foreach ($files as $file) {
                    $basename = pathinfo($file['name'], PATHINFO_BASENAME);
                    $serverFiles[] = $basename;
                }
            } catch (\Exception $ex) {
                Log::warning('failed to get list of files for rust wipe', [
                    'ex' => $ex
                ]);

                throw new DisplayException('Failed to get list of files to wipe - does the /server/rust directory exist?');
            }
        }

        if (count($serverFiles) < 1) {
            throw new DisplayException('Server already wiped.');
        }

        // Delete files
        $fileRepository->deleteFiles('/server/rust', $serverFiles);
    }
}
