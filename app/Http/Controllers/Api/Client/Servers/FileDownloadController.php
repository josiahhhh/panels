<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Facades\Activity;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Repositories\Wings\DaemonFileDownloadRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\FileDownloadRequest;

class FileDownloadController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonFileDownloadRepository
     */
    private $fileRepository;

    /**
     * FileDownloadController constructor.
     * @param DaemonFileDownloadRepository $fileRepository
     */
    public function __construct(DaemonFileDownloadRepository $fileRepository)
    {
        parent::__construct();

        $this->fileRepository = $fileRepository;
    }

    /**
     * @param FileDownloadRequest $request
     * @param Server $server
     * @return JsonResponse
     * @throws -1 \Throwable
     */
    public function download(FileDownloadRequest $request, Server $server)
    {
        $this->fileRepository
            ->setServer($server)
            ->downloadUrl($request->input('url'), $request->input('root', '/'));

        Activity::event('server:file.download')->property('file', $request->get('url'))->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
