<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Logs;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DeleteServerLogsRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return 'logs.delete';
    }
}
