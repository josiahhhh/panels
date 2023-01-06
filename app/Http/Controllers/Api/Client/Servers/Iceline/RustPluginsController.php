<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class RustPluginsController extends ClientApiController
{

    /**
     * @var DaemonFileRepository
     */
    private $fileRepository;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @param DaemonFileRepository $fileRepository
     */
    public function __construct(DaemonFileRepository $fileRepository)
    {
        parent::__construct();

        $this->fileRepository = $fileRepository;

        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push(\Sentry\Tracing\GuzzleTracingMiddleware::trace());
        $client = new Client([
            'handler' => $stack,
        ]);
        $this->client = $client;
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return array[]
     * @throws DisplayException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function installed(Request $request, Server $server)
    {
        $installedPlugins = [];

        try {
            $files = $this->fileRepository->setServer($server)->getDirectory('/oxide/plugins');

            foreach ($files as $file) {
                $filename = pathinfo($file['name'], PATHINFO_FILENAME);

                $pluginName = $filename;

                $plugin = [
                    'filename' => $file['name'],
                    'name' => $pluginName
                ];

                try {
                    $response = $this->client->get("https://umod.org/plugins/" . $pluginName . ".json");
                    $pluginManifest = json_decode($response->getBody(), true);
                    $plugin['manifest'] = $pluginManifest;
                } catch (\Exception $ex) {
                    Log::warning('Failed to retrieve umod plugin manifest for installed plugin. This is probably because the plugin was manually added by the user and there\'s a high chance this error can be disregarded', [
                        'plugin_name' => $pluginName,
                        'ex' => $ex
                    ]);
                }

                $installedPlugins[] = $plugin;
            }
        } catch (DaemonConnectionException $ex) {
            if ($ex->getStatusCode() !== 404 && $ex->getStatusCode() !== 500) {
                Log::error('error occurred while attempting to retrieve the rust plugin list', [
                    'ex' => $ex,
                    'message' => $ex->getMessage(),
                    'status_code' => $ex->getStatusCode()
                ]);

                throw new DisplayException('An unexpected error occurred while fetching the installed plugin list.');
            }
        }

        return [
            'plugins' => $installedPlugins
        ];
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function list(Request $request, Server $server)
    {
        $response = $this->client->get('https://umod.org/plugins/search.json', [
            'query' => [
                'query' => $request->query('query', ''),
                'page' => $request->query('page', '1'),
                'sort' => 'downloads',
                'sortdir' => 'desc',
                'filter' => '',
                'categories%5B%5D' => 'rust',
                'author' => '',
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return bool[]
     * @throws DaemonConnectionException
     */
    public function status(Request $request, Server $server)
    {
        $foundOxide = false;
        $rootFiles = $this->fileRepository->setServer($server)->getDirectory("/");
        foreach ($rootFiles as $file) {
            if ($file['name'] == 'oxide') {
                $foundOxide = true;
            }
        }

        return [
            'foundOxide' => $foundOxide
        ];
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return bool[]
     * @throws DaemonConnectionException
     * @throws DisplayException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException
     */
    public function install(Request $request, Server $server)
    {
        $validatedData = $request->validate([
            'plugin' => 'required'
        ]);

        $plugin_slug = $validatedData['plugin'];

        $response = $this->client->get("https://umod.org/plugins/" . $plugin_slug . ".json");
        $pluginManifest = json_decode($response->getBody(), true);

        $response = $this->client->get("https://umod.org/plugins/" . $plugin_slug . "/latest.json");
        $pluginVersionManifest = json_decode($response->getBody(), true);

        // Create the target directory just in case
        $this->fileRepository->setServer($server)->createDirectory('oxide', '/');
        $this->fileRepository->setServer($server)->createDirectory('plugins', '/oxide');

        // Download and copy the plugin to the server
        try {
            $response = $this->client->get($pluginVersionManifest['download_url']);
            $filename = "/oxide/plugins/" . $pluginManifest['name'] . '.cs';

            try {
                $this->fileRepository->setServer($server)->getContent($filename);
            } catch (DaemonConnectionException $ex) {
                if ($ex->getStatusCode() == 404 || $ex->getStatusCode() == 500) {
                    // Write the plugin to the server
                    $this->fileRepository->setServer($server)->putContent($filename, $response->getBody()->getContents());

                    return [
                        'success' => true
                    ];
                } else {
                    Log::error('error occurred while attempting to check if plugin already exists', [
                        'ex' => $ex,
                        'plugin' => $plugin_slug,
                        'message' => $ex->getMessage(),
                        'status_code' => $ex->getStatusCode()
                    ]);

                    throw new DisplayException('An unexpected error occurred while checking if plugin is already installed.');
                }
            }

            throw new DisplayException('Plugin is already installed on the server, please manually remove it from ' . $filename . ' before attempting to install it again.');
        } catch (DaemonConnectionException $ex) {
            Log::error('error occurred while attempting to put plugin files on the server', [
                'ex' => $ex,
                'plugin' => $plugin_slug,
                'message' => $ex->getMessage()
            ]);

            throw new DisplayException('Failed to transfer plugin to server, check that you have enough storage space available.');
        }
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return void
     * @throws DisplayException
     */
    public function uninstall(Request $request, Server $server)
    {
        $validatedData = $request->validate([
            'filename' => 'required'
        ]);

        $pluginFilename = $validatedData['filename'];

        try {
            $this->fileRepository->setServer($server)->deleteFiles('/oxide/plugins', [
                $pluginFilename
            ]);
        } catch (DaemonConnectionException $ex) {
            if ($ex->getStatusCode() == 404) {
                throw new DisplayException('Couldn\'t find plugin file to delete, has it been deleted already?');
            } else {
                Log::error('error occurred while attempting to remove rust plugin', [
                    'ex' => $ex,
                    'filename' => $pluginFilename,
                    'message' => $ex->getMessage(),
                    'status_code' => $ex->getStatusCode()
                ]);

                throw new DisplayException('An unexpected error occurred while attempting to remove the plugin.');
            }
        }
    }
}
