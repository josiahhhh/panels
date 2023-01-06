<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\ModsRequest;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use function GuzzleHttp\Psr7\stream_for;

class ModsController extends ClientApiController {
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settingsRepository;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonFileRepository
     */
    private $fileRepository;

    /**
     * @var DaemonPowerRepository
     */
    private $powerRepository;

    private $modCacheDirectory = '/tmp/pterodactyl/mods';

    /**
     * @var Encrypter
     */
    private $encrypter;

    /**
     * SubdomainController constructor.
     * @param SettingsRepositoryInterface $settingsRepository
     * @param \Pterodactyl\Repositories\Wings\DaemonFileRepository $fileRepository
     * @param DaemonPowerRepository $powerRepository
     * @param Encrypter $encrypter
     */
    public function __construct(
        SettingsRepositoryInterface $settingsRepository,
        DaemonFileRepository $fileRepository,
        DaemonPowerRepository $powerRepository,
        Encrypter $encrypter
    ) {
        parent::__construct();

        $this->settingsRepository = $settingsRepository;
        $this->fileRepository = $fileRepository;
        $this->powerRepository = $powerRepository;
        $this->encrypter = $encrypter;

        // Create the mod cache directory if it doesn't exist
        if (!file_exists($this->modCacheDirectory)) {
            mkdir($this->modCacheDirectory, 0777, true);
        }
    }

    /**
     * @param ModsRequest $request
     * @param Server $server
     * @return array
     */
    public function index(ModsRequest $request, Server $server): array {
        $mods = [];
        $allMods = DB::table('mod_manager_mods')->get();
        $installedMods = DB::table('mod_manager_installed_mods')->where('server_id', '=', $server->id)->get();

        foreach ($installedMods as $installedMod) {
            foreach ($allMods as $mod) {
                if ($mod->id == $installedMod->mod_id) {
                    $mods[] = [
                        'id' => $mod->id,
                        'name' => $mod->name,
                        'image' => $mod->image,
                        'description' => $mod->description,
                        'installing' => $installedMod->installing,
                        'installError' => $installedMod->install_error,
                    ];
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'mods' => $mods,
            ],
        ];
    }

    /**
     * @param ModsRequest $request
     * @param Server $server
     * @return array
     */
    public function available(ModsRequest $request, Server $server): array {
        $mods = [];
        $allMods = DB::table('mod_manager_mods')->get();

        // TODO: remove installed mods from available list?

        foreach ($allMods as $mod) {
            $egg_ids = explode(',', $mod->egg_ids);
            foreach ($egg_ids as $egg_id) {
                if ($server->egg_id == $egg_id) {
                    $mods[] = [
                        'id' => $mod->id,
                        'name' => $mod->name,
                        'image' => $mod->image,
                        'description' => $mod->description,
                        'hasSQL' => $mod->mod_sql !== '',
                        'willRestart' => $mod->restart_on_install
                    ];
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'mods' => $mods,
            ],
        ];
    }

    protected function getFilename($url){
        $headers = get_headers($url,true);
        $headers = array_change_key_case($headers, CASE_LOWER);

        $contentDisposition = isset($headers['content-disposition']) ? $headers['content-disposition'] : null;
        $fileNameSubstr = $contentDisposition != null ? strstr($contentDisposition, "=") : null ;
        $fileName = trim($fileNameSubstr,"=\"'");

        Log::info("extracted filename from content-disposition header", [
            'headers' => $headers,
            'header' => $contentDisposition,
            'fileNameSubstr' => $fileNameSubstr,
            'fileName' => $fileName
        ]);

        return $fileName;
    }

    /**
     * @param ModsRequest $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function install(ModsRequest $request, Server $server): array {
        $this->validate($request, [
            'modId' => 'required|integer'
        ]);

        // Get the mod data from the database
        $mod_id = (int) $request->input('modId');
        $mod = DB::table('mod_manager_mods')->where('id', '=', $mod_id)->get();
        if (count($mod) < 1) {
            throw new DisplayException('Mod not found.');
        }
        $mod = $mod[0];

        // Check if the mod is already installed
        $installedModCheck = DB::table('mod_manager_installed_mods')->where('mod_id', '=', $mod_id)->where('server_id', '=', $server->id)->get();
        if (count($installedModCheck) >= 1) {
            throw new DisplayException('Mod already installed.');
        }

        // Add the mod to the list of installed mods for the servers
        DB::table('mod_manager_installed_mods')->insert([
            'server_id' => $server->id,
            'mod_id' => $mod_id,
            'installing' => true,
        ]);

        // File filepath of the mod in the local panel cache
        $cachedModFilename = sprintf('%s/%s.zip', $this->modCacheDirectory, $mod_id);

        // Only re-download the mod if it doesn't exist in the cache, the modification time
        // is newer then the current time, or TODO: the cache is manually flushed from a control panel button
        if ($mod->disable_cache || !file_exists($cachedModFilename) ||
            ($mod->updated_at != null && Carbon::parse($mod->updated_at)->gt(Carbon::createFromTimestamp(filemtime($cachedModFilename))))
        ) {
            Log::info("attempting to download mod to local cache", [
                'modId' => $mod_id,
                'cacheFilename '=> $cachedModFilename
            ]);
            try {
                // Download the URL to the cache
                $stack = \GuzzleHttp\HandlerStack::create();
                $stack->push(\Sentry\Tracing\GuzzleTracingMiddleware::trace());
                $client = new Client([
                    'handler' => $stack
                ]);
                $client->request('GET', $mod->mod_zip, ['sink' => $cachedModFilename]);
            } catch (Exception $ex) {
                Log::error('failed to download mod to local cache', [
                    'ex' => $ex
                ]);

                // Mark the mod as not installing on the server
                DB::table('mod_manager_installed_mods')->where('mod_id', '=', $mod_id)->where('server_id', '=', $server->id)->update([
                    'installing' => false,
                    'install_error' => 'Failed to download mod to node cache.'
                ]);

                throw new DisplayException('An error occurred while downloading the mod to the cache.');
            }
        }

        try {
            $targetFilename = $mod_id;

            $ext = pathinfo($mod->mod_zip, PATHINFO_EXTENSION);
            if ($ext == null || $ext == '') {
                Log::info('Couldn\'t extract extension from mod zip url, attempting to extract it from response headers', [
                    'ext' => $ext,
                    'mod_url' => $mod->mod_zip
                ]);

                $downloadFilename = $this->getFilename($mod->mod_zip);
                $ext = pathinfo($downloadFilename, PATHINFO_EXTENSION);
                $targetFilename = pathinfo($downloadFilename, PATHINFO_FILENAME);

                Log::info('Extracted mod filename and extension from request headers', [
                    'ext' => $ext,
                    'target_filename' => $targetFilename,
                    'mod_url' => $mod->mod_zip
                ]);
            }

            $remoteFilename = sprintf('%s.%s', $targetFilename, $ext);
            $remotePath = sprintf('%s/%s', $mod->install_folder, $remoteFilename);

            // Write the mod zip onto the server
            $resource = fopen($cachedModFilename, 'r');

            // Sanity check
            if (!$resource) {
                Log::error('failed to open mod file from cache');

                // Mark the mod as not installing on the server
                DB::table('mod_manager_installed_mods')->where('mod_id', '=', $mod_id)->where('server_id', '=', $server->id)->update([
                    'installing' => false,
                    'install_error' => 'An error occurred while attempting to read the mod cache.'
                ]);

                throw new DisplayException('An error occurred while attempting to read the mod cache.');
            }

            Log::info("attempting to transfer mod to server", [
                'mod' => $mod,
                'modId' => $mod_id,
                'cacheFilename '=> $cachedModFilename,
                'remotePath' => $remotePath
            ]);
            $stream = Utils::streamFor($resource);
            try {
                $this->fileRepository->setServer($server)->putContentStream(
                    $remotePath,
                    $stream
                );
            } catch (Exception $ex) {
                Log::error('failed to upload mod to server', [
                    'ex' => $ex
                ]);

                // Mark the mod as not installing on the server
                DB::table('mod_manager_installed_mods')->where('mod_id', '=', $mod_id)->where('server_id', '=', $server->id)->update([
                    'installing' => false,
                    'install_error' => 'An error occurred while transferring the mod to your server. Please ensure you have a suitable amount of disk space remaining.'
                ]);

                throw new DisplayException('An error occurred while transferring the mod to your server. Please ensure you have a suitable amount of disk space remaining.');
            }

            // Close the handle to the mod file
            fclose($resource);

            // Decompress the mod zip onto the server
            if ($ext == 'zip' || $ext == 'tar.gz') {
                Log::info("attempting to decompress mod on server", [
                    'mod' => $mod,
                    'modId' => $mod_id,
                    'cacheFilename '=> $cachedModFilename,
                    'remotePath' => $remotePath,
                    'target' => $mod->install_folder
                ]);

                try {
                    $this->fileRepository->setServer($server)->decompressFile(
                        $mod->install_folder,
                        $remoteFilename // relative to the root
                    );
                } catch (Exception $ex) {
                    Log::error('failed to decompress mod on server', [
                        'ex' => $ex
                    ]);

                    // Mark the mod as not installing on the server
                    DB::table('mod_manager_installed_mods')->where('mod_id', '=', $mod_id)->where('server_id', '=', $server->id)->update([
                        'installing' => false,
                        'install_error' => 'Failed to uncompress the mod zip at '.$remotePath.'. Please ensure you have sufficient disk space.'
                    ]);

                    throw new DisplayException('Failed to uncompress the mod zip at '.$remotePath.'. Please ensure you have sufficient disk space.');
                }

                // Delete the mod zip from the server after decompressing
                $this->fileRepository->setServer($server)->deleteFiles($mod->install_folder, [$remoteFilename]);
            }

            if ($mod->mod_sql !== null && $mod->mod_sql !== '') {
                $sqlFilename = sprintf('%s/%s.sql', $this->modCacheDirectory, $mod_id);

                // Download the SQL to a temp file
                if (file_put_contents($sqlFilename, file_get_contents($mod->mod_sql)) === false) {
                    throw new DisplayException('Failed to download mod sql');
                }

                if (count($server->databases) > 0) {
                    /** @var Database $database */
                    $database = $server->databases[0];

                    $cmd = sprintf(
                        'mysql --host=%s --port=%d --password=\'%s\' --user=%s %s < %s 2>&1',
                        $database->host->host,
                        $database->host->port,
                        $this->encrypter->decrypt($database->password),
                        $database->username,
                        $database->database,
                        $sqlFilename);

                    $output = NULL;
                    exec($cmd, $output, $result);

                    if ($result > 0) {
                        $combinedOutput = implode('; ', $output);
                        if (strpos($combinedOutput, 'ERROR 1062') !== false) {
                            throw new \Exception('Mod SQL already exists in the database, no database updates where made.');
                        }

                        throw new \Exception('error importing mod sql: exit code ' . $result . ' ' . implode('; ', $output));
                    }
                } else {
                    throw new \Exception('No server databases found, mod SQL was not imported.');
                }
            }

            if ($mod->restart_on_install) {
                $this->powerRepository->setServer($server)->send('restart');
            }
        } catch (Exception $e) {
            Log::error('Failed to install mod', [
                'mod_id' => $mod_id,
                'ex' => $e
            ]);

            // Log the installation error
            DB::table('mod_manager_installed_mods')->where('mod_id', '=', $mod_id)->where('server_id', '=', $server->id)->update([
                'install_error' => $e->getMessage(),
            ]);
        } finally {
            // Mark the mod as not installing on the server
            DB::table('mod_manager_installed_mods')->where('mod_id', '=', $mod_id)->where('server_id', '=', $server->id)->update([
                'installing' => false,
            ]);
        }

        if ($mod->disable_cache) {
            unlink($cachedModFilename);
        }

        return ['success' => true];
    }

    /**
     * @param ModsRequest $request
     * @param Server $server
     * @param $id
     * @return array
     * @throws DisplayException
     */
    public function uninstall(ModsRequest $request, Server $server, $id): array {
        $id = (int) $id;

        $mod = DB::table('mod_manager_mods')->where('id', '=', $id)->get();
        if (count($mod) < 1) {
            throw new DisplayException('Mod not found.');
        }
        $mod = $mod[0];

        try {
            // Remove the uninstall paths from the server
            $rawRemovePaths = preg_split("/(\r\n|\r|\n|;|,)/",
                $mod->uninstall_paths);

            $removePaths = [];
            foreach ($rawRemovePaths as $removePath) {
                if ($removePath != '' && $removePath != '/') {
                    $removePaths[] = $removePath;
                }
            }

            if (count($removePaths) > 0) {
                $this->fileRepository
                    ->setServer($server)
                    ->deleteFiles(
                        $mod->install_folder,
                        $removePaths);
            }
        } catch (Exception $e) {
            throw new DisplayException('Error while uninstalling mod: ' . $e->getMessage());
        } finally {
            // Delete the mod record
            DB::table('mod_manager_installed_mods')->where('mod_id', '=', $id)->where('server_id', '=', $server->id)->delete();
        }

        return ['success' => true];
    }
}
