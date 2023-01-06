<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Client;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Activity\AccountSubject;
use Pterodactyl\Http\Middleware\Api\Client\StaffMiddleware;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', [Client\ClientController::class, 'index'])->name('api:client.index');
Route::get('/permissions', [Client\ClientController::class, 'permissions']);

/*
|--------------------------------------------------------------------------
| Available Eggs API
|--------------------------------------------------------------------------
|
| Endpoint: /api/eggs
|
*/
Route::get('/eggs', [Client\AvailableEggsController::class, 'index']);

Route::prefix('/account')->middleware(AccountSubject::class)->group(function () {
    Route::prefix('/')->withoutMiddleware(RequireTwoFactorAuthentication::class)->group(function () {
        Route::get('/', [Client\AccountController::class, 'index'])->name('api:client.account');
        Route::get('/two-factor', [Client\TwoFactorController::class, 'index']);
        Route::post('/two-factor', [Client\TwoFactorController::class, 'store']);
        Route::delete('/two-factor', [Client\TwoFactorController::class, 'delete']);
    });

    Route::put('/email', [Client\AccountController::class, 'updateEmail'])->name('api:client.account.update-email');
    Route::put('/password', [Client\AccountController::class, 'updatePassword'])->name('api:client.account.update-password');

    Route::get('/activity', Client\ActivityLogController::class)->name('api:client.account.activity');

    Route::get('/api-keys', [Client\ApiKeyController::class, 'index']);
    Route::post('/api-keys', [Client\ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{identifier}', [Client\ApiKeyController::class, 'delete']);

    Route::prefix('/ssh-keys')->group(function () {
        Route::get('/', [Client\SSHKeyController::class, 'index']);
        Route::post('/', [Client\SSHKeyController::class, 'store']);
        Route::post('/remove', [Client\SSHKeyController::class, 'delete']);
    });

    Route::prefix('staff')->middleware(StaffMiddleware::class)->group(function () {
        Route::get('/', [Client\StaffController::class, 'index']);
        Route::post('/request', [Client\StaffController::class, 'request']);
        Route::delete('/delete/{id}', [Client\StaffController::class, 'delete']);
    });
});

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
        ResourceBelongsToServer::class,
    ],
], function () {
    Route::get('/', [Client\Servers\ServerController::class, 'index'])->name('api:client:server.view');
    Route::get('/websocket', Client\Servers\WebsocketController::class)->name('api:client:server.ws');
    Route::get('/resources', Client\Servers\ResourceUtilizationController::class)->name('api:client:server.resources');
    Route::get('/activity', Client\Servers\ActivityLogController::class)->name('api:client:server.activity');

    Route::post('/command', [Client\Servers\CommandController::class, 'index']);
    Route::post('/power', [Client\Servers\PowerController::class, 'index']);

    Route::get('/restoring', [Client\Servers\Iceline\BackupRestoreStatusController::class, 'index']);

    Route::group(['prefix' => '/databases'], function () {
        Route::get('/', [Client\Servers\DatabaseController::class, 'index']);
        Route::post('/', [Client\Servers\DatabaseController::class, 'store']);
        Route::post('/{database}/rotate-password', [Client\Servers\DatabaseController::class, 'rotatePassword']);
        Route::delete('/{database}', [Client\Servers\DatabaseController::class, 'delete']);
    });

    Route::group(['prefix' => '/files'], function () {
        Route::get('/list', [Client\Servers\FileController::class, 'directory']);
        Route::get('/contents', [Client\Servers\FileController::class, 'contents']);
        Route::get('/download', [Client\Servers\FileController::class, 'download']);
        Route::put('/rename', [Client\Servers\FileController::class, 'rename']);
        Route::post('/copy', [Client\Servers\FileController::class, 'copy']);
        Route::post('/write', [Client\Servers\FileController::class, 'write']);
        Route::post('/compress', [Client\Servers\FileController::class, 'compress']);
        Route::post('/decompress', [Client\Servers\FileController::class, 'decompress']);
        Route::post('/delete', [Client\Servers\FileController::class, 'delete']);
        Route::post('/create-folder', [Client\Servers\FileController::class, 'create']);
        Route::post('/chmod', [Client\Servers\FileController::class, 'chmod']);
        Route::post('/pull', [Client\Servers\FileController::class, 'pull'])->middleware(['throttle:10,5']);
        Route::get('/upload', Client\Servers\FileUploadController::class);
        Route::post('/download-url', [Client\Servers\FileDownloadController::class, 'download']);
        Route::post('/importer', [Client\Servers\FileController::class, 'importer']);
    });

    Route::group(['prefix' => '/schedules'], function () {
        Route::get('/', [Client\Servers\ScheduleController::class, 'index']);
        Route::post('/', [Client\Servers\ScheduleController::class, 'store']);
        Route::get('/{schedule}', [Client\Servers\ScheduleController::class, 'view']);
        Route::post('/{schedule}', [Client\Servers\ScheduleController::class, 'update']);
        Route::post('/{schedule}/execute', [Client\Servers\ScheduleController::class, 'execute']);
        Route::delete('/{schedule}', [Client\Servers\ScheduleController::class, 'delete']);

        Route::post('/{schedule}/tasks', [Client\Servers\ScheduleTaskController::class, 'store']);
        Route::post('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'update']);
        Route::delete('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'delete']);
    });

    Route::group(['prefix' => '/network'], function () {
        Route::get('/allocations', [Client\Servers\NetworkAllocationController::class, 'index']);
        Route::post('/allocations', [Client\Servers\NetworkAllocationController::class, 'store']);
        Route::post('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'update']);
        Route::post('/allocations/{allocation}/primary', [Client\Servers\NetworkAllocationController::class, 'setPrimary']);
        Route::delete('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'delete']);
    });

    Route::group(['prefix' => '/users'], function () {
        Route::get('/', [Client\Servers\SubuserController::class, 'index']);
        Route::post('/', [Client\Servers\SubuserController::class, 'store']);
        Route::get('/{user}', [Client\Servers\SubuserController::class, 'view']);
        Route::post('/{user}', [Client\Servers\SubuserController::class, 'update']);
        Route::delete('/{user}', [Client\Servers\SubuserController::class, 'delete']);
    });

    Route::group(['prefix' => '/backups'], function () {
        Route::get('/', [Client\Servers\BackupController::class, 'index']);
        Route::post('/', [Client\Servers\BackupController::class, 'store']);
        Route::get('/{backup}', [Client\Servers\BackupController::class, 'view']);
        Route::get('/{backup}/download', [Client\Servers\BackupController::class, 'download']);
        Route::post('/{backup}/lock', [Client\Servers\BackupController::class, 'toggleLock']);
        Route::post('/{backup}/restore', [Client\Servers\BackupController::class, 'restore']);
        Route::delete('/{backup}', [Client\Servers\BackupController::class, 'delete']);

        Route::post('/{backup}/restore/iceline', [Client\Servers\Iceline\RestoreBackupController::class, 'restore']);
    });

    Route::get('/backups-size', [Client\Servers\BackupController::class, 'size']);

    Route::group(['prefix' => '/backups-database'], function () {
        Route::get('/', [Client\Servers\Iceline\DatabaseBackupController::class, 'index']);
        Route::post('/', [Client\Servers\Iceline\DatabaseBackupController::class, 'create']);
        Route::post('/{id}/restore', [Client\Servers\Iceline\DatabaseBackupController::class, 'restore']);
        Route::get('/{id}/download', [Client\Servers\Iceline\DatabaseBackupController::class, 'download']);
        Route::delete('/{id}', [Client\Servers\Iceline\DatabaseBackupController::class, 'delete']);
    });

    Route::group(['prefix' => '/startup'], function () {
        Route::get('/', [Client\Servers\StartupController::class, 'index']);
        Route::put('/variable', [Client\Servers\StartupController::class, 'update']);
    });

    Route::prefix('/staff')->withoutMiddleware(StaffMiddleware::class)->group(function () {
        Route::get('/', [Client\Servers\StaffController::class, 'index']);

        Route::group(['prefix' => '/{id}'], function () {
            Route::post('/accept', [Client\Servers\StaffController::class, 'accept']);
            Route::post('/deny', [Client\Servers\StaffController::class, 'deny']);
        });
    });

    Route::group(['prefix' => '/logs'], function () {
        Route::get('/', [Client\Servers\LogsController::class, 'index']);
        Route::delete('/delete/{id}', [Client\Servers\LogsController::class, 'delete']);
        Route::delete('/delete', [Client\Servers\Iceline\ServerLogsController::class, 'delete']);
    });

    Route::group(['prefix' => 'eggs'], function () {
        Route::get('/', [Client\Servers\EggChangerController::class, 'index']);
        Route::post('/change', [Client\Servers\EggChangerController::class, 'change']);
    });

    Route::group(['prefix' => '/settings'], function () {
        Route::post('/rename', [Client\Servers\SettingsController::class, 'rename']);
        Route::post('/reinstall', [Client\Servers\SettingsController::class, 'reinstall']);
        Route::put('/docker-image', [Client\Servers\SettingsController::class, 'dockerImage']);

        Route::group(['prefix' => '/backups'], function () {
            Route::get('/', [Client\Servers\Iceline\BackupSettingsController::class, 'index']);
            Route::post('/', [Client\Servers\Iceline\BackupSettingsController::class, 'update']);
        });
    });

	/* Route::group(['prefix' => '/subdomain'], function () {
		Route::get('/', [Client\Servers\SubdomainController::class, 'index']);
		Route::post('/create', [Client\Servers\SubdomainController::class, 'create']);
		Route::delete('/delete/{id}', [Client\Servers\SubdomainController::class, 'delete']);
        Route::post('/sync/{id}', [Client\Servers\SubdomainController::class, 'sync']);
	}); */

    Route::group(['prefix' => '/mods'], function () {
        Route::get('/', [Client\Servers\ModsController::class, 'index']);
        Route::get('/available', [Client\Servers\ModsController::class, 'available']);
        Route::post('/install', [Client\Servers\ModsController::class, 'install']);
        Route::delete('/uninstall/{id}', [Client\Servers\ModsController::class, 'uninstall']);
    });

    Route::group(['prefix' => '/transfer'], function () {
        Route::post('/', [Client\Servers\Iceline\ServerTransferController::class, 'initiate']);
        Route::get('/locations', [Client\Servers\Iceline\ServerTransferController::class, 'locations']);
    });

    Route::group(['prefix' => '/players'], function () {
        Route::get('/count', [Client\Servers\Iceline\PlayersController::class, 'count']);
    });

    Route::group(['prefix' => '/minecraft'], function () {
        Route::get('/versions', [Client\Servers\Iceline\MinecraftVersionController::Class, 'versions']);
        Route::get('/versions/{flavor}', [Client\Servers\Iceline\MinecraftVersionController::class, 'flavor']);
        Route::post('/versions/change', [Client\Servers\Iceline\MinecraftVersionController::class, 'change']);

        Route::post('/plugins/install', [Client\Servers\Iceline\MinecraftPluginsController::class, 'install']);
    });

    Route::group(['prefix' => '/plugins'], function () {
        Route::get('/rust/plugins', [Client\Servers\Iceline\RustPluginsController::class, 'installed']);
        Route::get('/rust', [Client\Servers\Iceline\RustPluginsController::class, 'list']);
        Route::get('/rust/status', [Client\Servers\Iceline\RustPluginsController::class, 'status']);
        Route::post('/rust/install', [Client\Servers\Iceline\RustPluginsController::class, 'install']);
        Route::post('/rust/uninstall', [Client\Servers\Iceline\RustPluginsController::class, 'uninstall']);
    });

    Route::group(['prefix' => '/games'], function () {
        Route::post('/rust/wipe', [Client\Servers\Iceline\RustWipeController::class, 'index']);
    });
});
