<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonFileDownloadRepository extends DaemonRepository
{
    /**
     * @param string $url
     * @param string $path
     * @return ResponseInterface
     * @throws DaemonConnectionException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function downloadUrl(string $url, string $path): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/download-url', $this->server->uuid),
                [
                    'json' => [
                        'url' => $url,
                        'path' => $path,

                    ],
                    'timeout' => 120,
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
