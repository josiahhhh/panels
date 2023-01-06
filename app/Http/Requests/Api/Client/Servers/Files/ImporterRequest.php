<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ImporterRequest extends ClientApiRequest
{
    /**
     * Check that the user making this request to the API is authorized to list all
     * of the files that exist for a given server.
     */
    public function permission(): string
    {
        return Permission::ACTION_FILE_READ;
    }

    public function rules(): array
    {
        return [
            'user' => 'required|string',
            'password' => 'required|string',
            'hote' => 'required|string',
            'port' => 'required|numeric',
            'srclocation' => 'string',
            'dstlocation' => 'string',
            'wipe' => 'required|boolean',
            'type' => 'required|string',

        ];
    }
}

