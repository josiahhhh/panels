<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Mods;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\EggTransformer;
use Pterodactyl\Http\Requests\Api\Application\Nests\Eggs\GetEggRequest;
use Pterodactyl\Http\Requests\Api\Application\Nests\Eggs\GetEggsRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ModsController extends ApplicationApiController {
    /**
     * ModsController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $repository
     */
    public function __construct(EggRepositoryInterface $repository) {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return all mods that exist for a given egg.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nests\Eggs\GetEggsRequest $request
     * @return JsonResponse
     */
    public function index(GetEggsRequest $request): JsonResponse {
        // Get the egg ID from the request
        $query_egg_id = $request->query('egg_id');
        if ($query_egg_id === null) {
            return new JsonResponse([], JsonResponse::HTTP_NOT_FOUND);
        }
        $query_egg_id = (int)$query_egg_id;

        // Get the list of mods
        $mods = [];
        $allMods = DB::table('mod_manager_mods')->get();

        // Find the mods that are for the specified egg ID
        foreach ($allMods as $mod) {
            $egg_ids = explode(',', $mod->egg_ids);
            foreach ($egg_ids as $egg_id) {
                if ($query_egg_id == $egg_id) {
                    $mods[] = [
                        'id' => $mod->id,
                        'name' => $mod->name,
                        'image' => $mod->image,
                        'description' => $mod->description
                    ];
                }
            }
        }

        // Return the list of mods
        return new JsonResponse($mods, JsonResponse::HTTP_OK);
    }
}
