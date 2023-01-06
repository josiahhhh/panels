<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Iceline;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Iceline\RustWipeService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class RustWipeController extends ClientApiController
{
    /**
     * @var RustWipeService
     */
    protected $rustWipeService;

    /**
     * @param RustWipeService $rustWipeService
     */
    public function __construct(RustWipeService $rustWipeService)
    {
        parent::__construct();

        $this->rustWipeService = $rustWipeService;
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return JsonResponse
     * @throws DisplayException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function index(Request $request, Server $server)
    {
        $validatedData = $request->validate([
            'wipe' => 'required|in:blueprint,map,full',
        ]);

        $this->rustWipeService->handle($server, $validatedData['wipe']);

        return JsonResponse::create([], JsonResponse::HTTP_NO_CONTENT);
    }
}
