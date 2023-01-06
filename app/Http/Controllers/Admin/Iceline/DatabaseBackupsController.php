<?php

namespace Pterodactyl\Http\Controllers\Admin\Iceline;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use obregonco\B2\Client;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Iceline\DatabaseBackupManager;
use Pterodactyl\Http\Controllers\Api\Client\Servers\DownloadBackupController;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Iceline\DatabaseBackup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Services\Backups\DeleteBackupService;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Transformers\Api\Client\BackupTransformer;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DatabaseBackupsController extends Controller {

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var DatabaseBackupManager
     */
    private $databaseBackupManager;

    /**
     * SubDomainController constructor.
     * @param AlertsMessageBag $alert
     * @param DatabaseBackupManager $databaseBackupManager
     */
    public function __construct(
        AlertsMessageBag $alert,
        DatabaseBackupManager $databaseBackupManager
    ) {
        $this->alert = $alert;
        $this->databaseBackupManager = $databaseBackupManager;
    }

    /**
     * Returns a list of all types of backups.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_backups != 1 && $role->p_backups != 2) {return abort(403);}}}

        $backups = QueryBuilder::for(DatabaseBackup::query())->paginate(25);
        $servers = Server::all();

        return view('admin.backups.database.index', [
            'backups' => $backups,
            'servers' => $servers
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new() {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_backups != 1 && $role->p_backups != 2) {return abort(403);}}}

        $servers = DB::table('servers')->get();

        return view('admin.backups.database.new', [
            'servers' => $servers
        ]);
    }

    /**
     * Creates a file backup from the admin panel.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_backups != 2) {return abort(403);}}}

        $this->validate($request, [
            'name' => 'required|min:1|max:100',
            'server_id' => 'required',
            'database_id' => 'required'
        ]);

        $name = trim(strip_tags($request->input('name')));

        $server_id = $request->input('server_id');
        $database_id = $request->input('database_id');

        // TODO: Create backup

        $this->alert->success('You have successfully initiated a new database backup.')->flash();
        return redirect()->route('admin.backups.database');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_backups != 2) {return abort(403);}}}

        $backup_id = (int) $request->input('id', '');

        $backup = DatabaseBackup::where('id' ,'=', (string)$backup_id)->first();
        if ($backup == null) {
            throw new DisplayException('Cannot find database backup with id ' . $backup_id);
        }

        // Delete the backup
        $this->databaseBackupManager->delete($backup->uuid);

        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function download(Request $request, Server $server, $id) {
        $roles = DB::table('permissions')->get();foreach($roles as $role) {if($role->id == Auth::user()->role) {if($role->p_backups != 2) {return abort(403);}}}

        $backup_id = (int) $id;

        /** @var DatabaseBackup $backup */
        $backup = DatabaseBackup::where('id' ,'=', (string)$backup_id)->first();
        if ($backup == null) {
            throw new DisplayException('Cannot find database backup with id ' . $id);
        }

        // Get the download URL for the backup
        $url = $this->databaseBackupManager->getDownloadURL($backup->uuid);

        return Redirect::to($url);
    }
}
