<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MinecraftVersionController extends ClientApiController {

    /**
     * @var DaemonFileRepository
     */
    private $fileRepository;

    /**
     * @var VariableValidatorService
     */
    private $validatorService;

    /**
     * @var ServerVariableRepository
     */
    private $variableRepository;


    /**
     * BackupController constructor.
     * @param DaemonFileRepository $fileRepository
     * @param VariableValidatorService $service
     * @param ServerVariableRepository $repository
     */
    public function __construct(
        DaemonFileRepository $fileRepository,
        VariableValidatorService $service,
        ServerVariableRepository $repository
    ) {
        parent::__construct();

        $this->fileRepository = $fileRepository;
        $this->validatorService = $service;
        $this->variableRepository = $repository;
    }

    /**
     * Returns a list of available versions.
     *
     * @param Request $request
     * @param Server $server
     * @return array
     */
    public function versions(Request $request, Server $server) {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 'paper',
                    'name' => 'Paper',
                ], [
                    'id' => 'waterfall',
                    'name' => 'Waterfall',
                ], [
                    'id' => 'spigot',
                    'name' => 'Spigot',
                ], [
                    'id' => 'vanilla',
                    'name' => 'Vanilla',
                ], [
                    'id' => 'bukkit',
                    'name' => 'Bukkit',
                ]
            ]
        ];
    }

    /**
     * Returns a list of available versions for the minecraft flavor.
     *
     * @param Request $request
     * @param Server $server
     * @param string $flavor
     * @return array
     */
    public function flavor(Request $request, Server $server, string $flavor) {
        $versions = [];

        switch ($flavor) {
        case "paper":
            $paperVersions = json_decode(file_get_contents("https://papermc.io/api/v2/projects/paper"), true);
            foreach ($paperVersions["versions"] as $paperVersion) {
                Log::info('getting paper versions builds', [
                    'version' => $paperVersion
                ]);

                $versions[] = [
                    'id' => $paperVersion,
                    'name' => $paperVersion,
                ];
            }
           break;
        case "waterfall":
            $waterfallVersions = json_decode(file_get_contents("https://papermc.io/api/v1/waterfall"), true);
            foreach ($waterfallVersions["versions"] as $waterfallVersion) {
                Log::info('getting paper versions builds', [
                    'version' => $waterfallVersion
                ]);

                $versions[] = [
                    'id' => $waterfallVersion,
                    'name' => $waterfallVersion,
                ];
            }
            break;
        case "vanilla":
            $minecraftVersionsManifest = json_decode(file_get_contents("https://launchermeta.mojang.com/mc/game/version_manifest.json"), true);
            foreach ($minecraftVersionsManifest['versions'] as $minecraftVersion) {
                if ($minecraftVersion['type'] == 'snapshot') {
                    continue;
                }

                $versions[] = [
                    'id' => $minecraftVersion['id'],
                    'name' => $minecraftVersion['id'],
                    'type' => $minecraftVersion['type'],
                ];
            }
            break;
            case "spigot":
                // Run:
                // let versions = []; $('.download-pane').find('h2').each(function () {versions.push($(this).html())}); console.log(versions)
                // On: https://getbukkit.org/download/spigot
                $spigotVersions = ["1.16.4", "1.16.3", "1.16.2", "1.16.1", "1.15.2", "1.15.1", "1.15", "1.14.4", "1.14.3", "1.14.2", "1.14.1", "1.14", "1.13.2", "1.13.1", "1.13", "1.12.2", "1.12.1", "1.12", "1.11.2", "1.11.1", "1.11", "1.10.2", "1.10", "1.9.4", "1.9.2", "1.9", "1.8.8", "1.8.7", "1.8.6", "1.8.5", "1.8.4", "1.8.3", "1.8", "1.7.10", "1.7.9", "1.7.8", "1.7.5", "1.7.2", "1.6.4", "1.6.2", "1.5.2", "1.5.1", "1.4.7", "1.4.6"];

                foreach ($spigotVersions as $spigotVersion) {
                    $versions[] = [
                        'id' => $spigotVersion,
                        'name' => $spigotVersion,
                    ];
                }
                break;
            case "bukkit":
                // Run:
                // let versions = []; $('.download-pane').find('h2').each(function () {versions.push($(this).html())}); console.log(versions)
                // On: https://getbukkit.org/download/craftbukkit
                $bukkitVersions = ["1.16.4", "1.16.3", "1.16.2", "1.16.1", "1.15.2", "1.15.1", "1.15", "1.14.4", "1.14.3", "1.14.2", "1.14.1", "1.14", "1.13.2", "1.13.1", "1.13", "1.12.2", "1.12.1", "1.12", "1.11.2", "1.11.1", "1.11", "1.10.2", "1.10", "1.9.4", "1.9.2", "1.9", "1.8.8", "1.8.7", "1.8.6", "1.8.5", "1.8.4", "1.8.3", "1.8", "1.7.10", "1.7.9", "1.7.8", "1.7.5", "1.7.2", "1.6.4", "1.6.2", "1.6.1", "1.5.2", "1.5.1", "1.5", "1.4.7", "1.4.6", "1.4.5", "1.4.2", "1.3.2", "1.3.1", "1.2.5", "1.2.4", "1.2.3", "1.2.2", "1.1", "1.0.0"];

                foreach ($bukkitVersions as $bukkitVersion) {
                    $versions[] = [
                        'id' => $bukkitVersion,
                        'name' => $bukkitVersion,
                    ];
                }
                break;
        }

        return [
            'success' => true,
            'data' => $versions
        ];
    }

    public function change(Request $request, Server $server) {
        $validatedData = $request->validate([
            'flavor' => 'required',
            'version' => 'required'
        ]);

        $flavor_id = $validatedData['flavor'];
        $version_id = $validatedData['version'];

        $jar_path = $flavor_id . "-" . $version_id . ".jar";

        switch ($flavor_id) {
            case "vanilla":
                $minecraftVersionManifest = null;
                $minecraftVersionsManifest = json_decode(file_get_contents("https://launchermeta.mojang.com/mc/game/version_manifest.json"), true);

                foreach ($minecraftVersionsManifest['versions'] as $minecraftVersion) {
                    if ($minecraftVersion['id'] == $version_id) {
                        $minecraftVersionManifest = json_decode(file_get_contents($minecraftVersion['url']), true);

                        $downloadURL = file_get_contents($minecraftVersionManifest['downloads']['server']['url']);
                        $this->fileRepository->setServer($server)->putContent($jar_path, $downloadURL);

                        break;
                    }
                }

                if ($minecraftVersionManifest == null) {
                    throw new DisplayException("failed to find version " . $version_id . " for vanilla minecraft");
                }

                break;
            case "paper":
                $versionMeta = json_decode(file_get_contents("https://papermc.io/api/v2/projects/paper/versions/" . $version_id), true);
                // Get the newest build
                $build = $versionMeta['builds'][count($versionMeta['builds']) - 1];

                $buildMeta = json_decode(file_get_contents("https://papermc.io/api/v2/projects/paper/versions/" . $version_id . "/builds/" . $build), true);
                $downloadMeta = $buildMeta['downloads']['application'];

                $downloadURL = file_get_contents("https://papermc.io/api/v2/projects/paper/versions/" . $version_id . "/builds/" . $build . "/downloads/" . $downloadMeta['name']);
                $this->fileRepository->setServer($server)->putContent($jar_path, $downloadURL);
                break;
            case "waterfall":
                $downloadURL = file_get_contents("https://papermc.io/api/v1/waterfall/" . $version_id . "/latest/download");
                $this->fileRepository->setServer($server)->putContent($jar_path, $downloadURL);
                break;
            case "spigot":
                $downloadURL = file_get_contents("https://cdn.getbukkit.org/spigot/spigot-" . $version_id . ".jar");
                $this->fileRepository->setServer($server)->putContent($jar_path, $downloadURL);
                break;
            case "bukkit":
                $downloadURL = file_get_contents("https://cdn.getbukkit.org/craftbukkit/craftbukkit-" . $version_id . ".jar");
                $this->fileRepository->setServer($server)->putContent($jar_path, $downloadURL);
                break;
            default:
                break;
        }

        // Update the server jar name in the startup parameters
        {
            /** @var \Pterodactyl\Models\EggVariable $variable */
            $variable = $server->variables()->where('env_variable', 'SERVER_JARFILE')->first();

            if (is_null($variable) || ! $variable->user_viewable) {
                throw new BadRequestHttpException(
                    "The environment variable you are trying to edit does not exist."
                );
            } else if (! $variable->user_editable) {
                throw new BadRequestHttpException(
                    "The environment variable you are trying to edit is read-only."
                );
            }

            $this->variableRepository->updateOrCreate([
                'server_id' => $server->id,
                'variable_id' => $variable->id,
            ], [
                'variable_value' => $jar_path ?? '',
            ]);
        }

        return [
            'success' => true
        ];
    }
}
