<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

require __DIR__ . '/MinecraftPlugins/cloudflare.class.php';

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline\MinecraftPlugins\CloudFlare;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;

class MinecraftPluginsController extends ClientApiController {

    /**
     * @var DaemonFileRepository
     */
    private $fileRepository;

    /**
     * @var Client
     */
    private $client;

    /**
     * BackupController constructor.
     * @param DaemonFileRepository $fileRepository
     */
    public function __construct(
        DaemonFileRepository $fileRepository
    ) {
        parent::__construct();

        $this->fileRepository = $fileRepository;

        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push(\Sentry\Tracing\GuzzleTracingMiddleware::trace());
        $client = new Client([
            'handler'  => $stack
        ]);
        $this->client = $client;
    }

    protected function getFilename($url){
        $headers = get_headers($url,true);
        $headers = array_change_key_case($headers, CASE_LOWER);

        $contentDisposition = isset($headers['content-disposition']) ? $headers['content-disposition'] : null;
        if ($contentDisposition != null) {
            $fileNameSubstr = $contentDisposition != null ? strstr($contentDisposition, "=") : null;
            $fileName = trim($fileNameSubstr, "=\"'");

            Log::info("extracted filename from content-disposition header", [
                'headers' => $headers,
                'header' => $contentDisposition,
                'fileNameSubstr' => $fileNameSubstr,
                'fileName' => $fileName
            ]);

            return $fileName;
        } else {
            $fileName = isset($headers['x-bz-file-name']) ? $headers['x-bz-file-name'] : null;
            Log::info("extracted filename from x-bz-file-name header", [
                'headers' => $headers,
                'header' => $fileName,
                'fileName' => $fileName
            ]);
            return $fileName;
        }
    }

    public function install(Request $request, Server $server) {
        $validatedData = $request->validate([
            'resource' => 'required'
        ]);

        $resource_id = $validatedData['resource'];
        $response = $this->client->get("https://api.spiget.org/v2/resources/" . $resource_id);
        $resourceManifest = json_decode($response->getBody(), true);

        $response = $this->client->get("https://api.spiget.org/v2/resources/" . $resource_id . "/versions/" . $resourceManifest['version']['id']);
        $versionManifest =  json_decode($response->getBody(), true);

//        $downloadURL = file_get_contents('https://www.spigotmc.org/' . $resourceManifest['file']['url']);
//        $downloadURL = file_get_contents('https://api.spiget.org/v2/resources/' . $resource_id . '/versions/' . $resourceManifest['version']['id'] . '/download');
//        $this->fileRepository->setServer($server)->putContent($resourceManifest['name'] . '-' . $versionManifest['name'] . '.jar', $downloadURL);

//        $url = 'https://api.spiget.org/v2/resources/' . $resource_id . '/versions/' . $resourceManifest['version']['id'] . '/download';
//        $cloudflare = new CloudFlare($url, [true, "x.txt"]);
//        $result = $cloudflare->get("/");
//        $this->fileRepository->setServer($server)->putContent($resourceManifest['name'] . '-' . $versionManifest['name'] . '.jar', $result);

        $url = 'https://api.spiget.org/v2/resources/'. $resource_id . '/download';

        // Get the download filename extension
        $downloadFilename = $this->getFilename($url);
        $ext = pathinfo($downloadFilename, PATHINFO_EXTENSION);
        Log::info('got plugin extension', [
            'url' => $url,
            'ext' => $ext
        ]);

        if ($ext == null || $ext == '') {
            $ext = 'jar';
        }

        // Download the mod
        $cloudflare = new CloudFlare($url, [true, "x.txt"]);
        $result = $cloudflare->get('/');
        $this->fileRepository->setServer($server)->putContent('plugins/' . $resourceManifest['name'] . '-' . $versionManifest['name'] . '.' . $ext, (string)$result);

        return [
            'success' => true
        ];
    }
}
