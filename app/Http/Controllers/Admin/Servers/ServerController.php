<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Models\Server;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Contracts\View\Factory;
use Spatie\QueryBuilder\AllowedFilter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Filters\AdminServerFilter;
use Pterodactyl\Repositories\Eloquent\ServerRepository;

class ServerController extends Controller
{
    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private $view;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * ServerController constructor.
     *
     * @param \Illuminate\Contracts\View\Factory $view
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param PlayerCountService $playerCountService
     */
    public function __construct(
        Factory $view,
        ServerRepository $repository
    ) {
        $this->view = $view;
        $this->repository = $repository;
    }

    /**
     * Returns all of the servers that exist on the system using a paginated result set. If
     * a query is passed along in the request it is also passed to the repository function.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_servers != 1 && $role->p_servers != 2) {return abort(403);}}}

        $servers = QueryBuilder::for(Server::query()->with('node', 'user', 'allocation'))
            ->allowedFilters([
                AllowedFilter::exact('owner_id'),
                AllowedFilter::custom('*', new AdminServerFilter()),
            ])
            ->paginate(config()->get('pterodactyl.paginate.admin.servers'));


        return $this->view->make('admin.servers.index', ['servers' => $servers]);
    }
}
