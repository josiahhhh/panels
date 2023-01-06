<?php

namespace Pterodactyl\Http\Controllers\Admin\Iceline;

use BackblazeB2\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use BackblazeB2\Http\Client as HttpClient;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SystemBackupsController extends Controller
{

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * SubDomainController constructor.
     * @param AlertsMessageBag $alert
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(AlertsMessageBag $alert, SettingsRepositoryInterface $settings)
    {
        $this->alert = $alert;
        $this->settings = $settings;
    }

    /**
     * Returns a list of all system backups.
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

        $client = new Client(config('backups.disks.b2.key_id'), config('backups.disks.b2.application_key'), [
            'client' => new HttpClient(['exceptions' => true])
        ]);

        // node -> server -> backup
        $backups = [];

        // Determine bucket ID
        $buckets = $client->listBuckets();
        foreach ($buckets as $bucket) {
            if ($bucket->getName() === "il-gamenode-backups-v2") {
                $bucketID = $bucket->getId();
                break;
            }
        }

        // Retrieve the first level of directories, which is node ID's
        $nodeDirectories = $client->listFiles([
            "BucketId" => $bucketID, // bucke
            "Prefix" => "game-server-backup/", // prefix
            "Delimiter" => "/" // delimiter
        ]);

        foreach ($nodeDirectories as $nodeDirectory) {
            Log::info('got node backup directory', [
                '$nodeDirectory.name' => $nodeDirectory->getName()
            ]);

            $dates = $client->listFiles([
                "BucketId" => $bucketID, // bucket
                "Prefix" => $nodeDirectory->getName(), // prefix
                "Delimiter" => "/" // delimiter
            ]);

            foreach ($dates as $date) {
                $times = $client->listFiles([
                    "BucketId" => $bucketID, // bucket
                    "Prefix" => $date->getName(), // prefix
                    "Delimiter" => "/" // delimiter
                ]);

                foreach ($times as $time) {
                    $serverBackups = $client->listFiles([
                        "BucketId" => $bucketID, // bucket
                        "Prefix" => $time->getName(), // prefix
                    ]);

                    foreach ($serverBackups as $serverBackup) {
                        $backups[] = (object) [
                            'node' => basename($nodeDirectory->getName()),
                            'date' => basename($date->getName()),
                            'time' => basename($time->getName()),
                            'server' => basename($serverBackup->getName()),
                            'size' => $serverBackup->getSize(),
                            'file_id' => $serverBackup->getId(),
                            'file_path' => $serverBackup->getName(),
                        ];
                    }
                }
            }
        }

        $servers = DB::table('servers')->get();

        return view('admin.sysbackups.index', [
            'servers' => $servers,
            'backups' => $backups,
        ]);
    }

    /**
     * Download the given system game node backup.
     *
     * @param Request $request
     * @return RedirectResponse|never
     * @throws \obregonco\B2\Exceptions\CacheException
     */
    public function download(Request $request)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_backups != 1 && $role->p_backups != 2) {
                    return abort(403);
                }
            }
        }

        $client = new \obregonco\B2\Client(config('backups.disks.b2.key_id'), [
            'applicationKey' => config('backups.disks.b2.application_key'),
        ]);

        $downloadUrl = "";

        if ($request->query('file_path')) {
            $bucket = $client->getBucketFromName("il-gamenode-backups-v2");

            $downloadUrl = $client->getDownloadUrl(
                $bucket,
                $request->query('file_path'),
                true,
                5 * 60);
        } else {
            $this->alert->danger('Missing file path in download request.')->flash();

            return redirect()->route('admin.sysbackups');
        }

        return Redirect::to($downloadUrl);
    }

    /**
     * Retrieve a download link for the backup.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse|never
     * @throws \obregonco\B2\Exceptions\CacheException
     */
    public function link(Request $request)
    {
        $roles = DB::table('permissions')->get();
        foreach ($roles as $role) {
            if ($role->id == Auth::user()->role) {
                if ($role->p_backups != 1 && $role->p_backups != 2) {
                    return abort(403);
                }
            }
        }

        $client = new \obregonco\B2\Client(config('backups.disks.b2.key_id'), [
            'applicationKey' => config('backups.disks.b2.application_key'),
        ]);

        $downloadUrl = "";

        if ($request->query('file_path')) {
            $bucket = $client->getBucketFromName("il-gamenode-backups-v2");

            $downloadUrl = $client->getDownloadUrl(
                $bucket,
                $request->query('file_path'),
                true,
                5 * 60);
        } else {
            $this->alert->danger('Missing file path in download request.')->flash();

            return redirect()->route('admin.sysbackups');
        }

        return JsonResponse::create([
            'link' => $downloadUrl
        ], JsonResponse::HTTP_OK);
    }
}
