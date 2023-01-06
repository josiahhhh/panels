<?php

namespace Pterodactyl\Http\Controllers\Admin\Iceline;

use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use obregonco\B2\Client;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Http\Controllers\Api\Client\Servers\DownloadBackupController;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Services\Backups\DeleteBackupService;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Transformers\Api\Client\BackupTransformer;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FileBackupsController extends Controller
{

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Backups\DeleteBackupService
     */
    private $deleteBackupService;

    /**
     * SubDomainController constructor.
     * @param AlertsMessageBag $alert
     * @param DeleteBackupService $deleteBackupService
     */
    public function __construct(
        AlertsMessageBag    $alert,
        DeleteBackupService $deleteBackupService
    )
    {
        $this->alert = $alert;
        $this->deleteBackupService = $deleteBackupService;
    }

    /**
     * Returns a list of all types of backups.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_backups != 1 && $role->p_backups != 2) {
                    return abort(403);
                }
            }
        }

        //        $backups = DB::table('backups')->paginate(15);
        //        $backups = json_decode(json_encode($backups), true);

        $backups = QueryBuilder::for(Backup::query())->paginate(25);
        $servers = Server::all();

        return view('admin.backups.files.index', [
            'backups' => $backups,
            'servers' => $servers
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_backups != 1 && $role->p_backups != 2) {
                    return abort(403);
                }
            }
        }

        $servers = DB::table('servers')->get();

        return view('admin.backups.files.new', [
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
    public function create(Request $request)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_backups != 2) {
                    return abort(403);
                }
            }
        }

        $this->validate($request, [
            'name' => 'required|min:1|max:100',
            'server_id' => 'required'
        ]);

        $name = trim(strip_tags($request->input('name')));

        $server_id = $request->input('server_id');

        // TODO: Create backup

        $this->alert->success('You have successfully initiated a new file backup.')->flash();

        return redirect()->route('admin.backups');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_backups != 2) {
                    return abort(403);
                }
            }
        }

        $backup_id = (int) $request->input('id', '');

        $backup = Backup::find($backup_id);

        $this->deleteBackupService->handle($backup);

        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function download(Request $request, $id)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_backups != 2) {
                    return abort(403);
                }
            }
        }

        $backup_id = (int) $id;

        $backup = Backup::find($backup_id);
        $server = Server::find($backup->server_id);

        switch ($backup->disk) {
            case Backup::ADAPTER_BACKBLAZE_B2:
                $client = new Client(config('backups.disks.b2.key_id'), [
                    'applicationKey' => config('backups.disks.b2.application_key'),
                ]);

                $file = sprintf('%s/%s.tar.gz', $server->uuid, $backup->uuid);

                $url = $client->getDownloadUrl(config('backups.disks.b2.bucket_id'), $file, true, 5 * 60);

                break;
            default:
                Log::error('no admin download handler has been implemented for ' . $backup->disk . ' adapter');
                throw new BadRequestHttpException;
        }

        return Redirect::to($url);
    }
}
