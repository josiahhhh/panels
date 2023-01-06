<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;
use Pterodactyl\Http\Middleware\Admin\Servers\ServerInstalled;

Route::get('/', [Admin\BaseController::class, 'index'])->name('admin.index');

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/api
|
*/
Route::group(['prefix' => 'api'], function () {
    Route::get('/', [Admin\ApiController::class, 'index'])->name('admin.api.index');
    Route::get('/new', [Admin\ApiController::class, 'create'])->name('admin.api.new');

    Route::post('/new', [Admin\ApiController::class, 'store']);

    Route::delete('/revoke/{identifier}', [Admin\ApiController::class, 'delete'])->name('admin.api.delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/locations
|
*/
Route::group(['prefix' => 'locations'], function () {
    Route::get('/', [Admin\LocationController::class, 'index'])->name('admin.locations');
    Route::get('/view/{location:id}', [Admin\LocationController::class, 'view'])->name('admin.locations.view');

    Route::post('/', [Admin\LocationController::class, 'create']);
    Route::patch('/view/{location:id}', [Admin\LocationController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/databases
|
*/
Route::group(['prefix' => 'databases'], function () {
    Route::get('/', [Admin\DatabaseController::class, 'index'])->name('admin.databases');
    Route::get('/view/{host:id}', [Admin\DatabaseController::class, 'view'])->name('admin.databases.view');

    Route::post('/', [Admin\DatabaseController::class, 'create']);
    Route::patch('/view/{host:id}', [Admin\DatabaseController::class, 'update']);
    Route::delete('/view/{host:id}', [Admin\DatabaseController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/settings
|
*/
Route::group(['prefix' => 'settings'], function () {
    Route::get('/', [Admin\Settings\IndexController::class, 'index'])->name('admin.settings');
    Route::get('/mail', [Admin\Settings\MailController::class, 'index'])->name('admin.settings.mail');
    Route::get('/advanced', [Admin\Settings\AdvancedController::class, 'index'])->name('admin.settings.advanced');

    Route::post('/mail/test', [Admin\Settings\MailController::class, 'test'])->name('admin.settings.mail.test');

    Route::patch('/', [Admin\Settings\IndexController::class, 'update']);
    Route::patch('/mail', [Admin\Settings\MailController::class, 'update']);
    Route::patch('/advanced', [Admin\Settings\AdvancedController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/users
|
*/
Route::group(['prefix' => 'users'], function () {
    Route::get('/', [Admin\UserController::class, 'index'])->name('admin.users');
    Route::get('/accounts.json', [Admin\UserController::class, 'json'])->name('admin.users.json');
    Route::get('/new', [Admin\UserController::class, 'create'])->name('admin.users.new');
    Route::get('/view/{user:id}', [Admin\UserController::class, 'view'])->name('admin.users.view');

    Route::post('/new', [Admin\UserController::class, 'store']);

    Route::patch('/view/{user:id}', [Admin\UserController::class, 'update']);
    Route::delete('/view/{user:id}', [Admin\UserController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/servers
|
*/
Route::group(['prefix' => 'servers'], function () {
    Route::get('/', [Admin\Servers\ServerController::class, 'index'])->name('admin.servers');
    Route::get('/new', [Admin\Servers\CreateServerController::class, 'index'])->name('admin.servers.new');
    Route::get('/view/{server:id}', [Admin\Servers\ServerViewController::class, 'index'])->name('admin.servers.view');

    Route::group(['middleware' => [ServerInstalled::class]], function () {
        Route::get('/view/{server:id}/details', [Admin\Servers\ServerViewController::class, 'details'])->name('admin.servers.view.details');
        Route::get('/view/{server:id}/build', [Admin\Servers\ServerViewController::class, 'build'])->name('admin.servers.view.build');
        Route::get('/view/{server:id}/startup', [Admin\Servers\ServerViewController::class, 'startup'])->name('admin.servers.view.startup');
        Route::get('/view/{server:id}/database', [Admin\Servers\ServerViewController::class, 'database'])->name('admin.servers.view.database');
        Route::get('/view/{server:id}/mounts', [Admin\Servers\ServerViewController::class, 'mounts'])->name('admin.servers.view.mounts');
    });

    Route::get('/view/{server:id}/manage', [Admin\Servers\ServerViewController::class, 'manage'])->name('admin.servers.view.manage');
    Route::get('/view/{server:id}/delete', [Admin\Servers\ServerViewController::class, 'delete'])->name('admin.servers.view.delete');

    Route::post('/new', [Admin\Servers\CreateServerController::class, 'store']);
    Route::post('/view/{server:id}/build', [Admin\ServersController::class, 'updateBuild']);
    Route::post('/view/{server:id}/startup', [Admin\ServersController::class, 'saveStartup']);
    Route::post('/view/{server:id}/database', [Admin\ServersController::class, 'newDatabase']);
    Route::post('/view/{server:id}/mounts', [Admin\ServersController::class, 'addMount'])->name('admin.servers.view.mounts.store');
    Route::post('/view/{server:id}/manage/toggle', [Admin\ServersController::class, 'toggleInstall'])->name('admin.servers.view.manage.toggle');
    Route::post('/view/{server:id}/manage/transfer/fix', [Admin\ServersController::class, 'fixStalledTransfer'])->name('admin.servers.view.manage.fixTransfer');
    Route::post('/view/{server:id}/manage/suspension', [Admin\ServersController::class, 'manageSuspension'])->name('admin.servers.view.manage.suspension');
    Route::post('/view/{server:id}/manage/reinstall', [Admin\ServersController::class, 'reinstallServer'])->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{server:id}/manage/transfer', [Admin\Servers\ServerTransferController::class, 'transfer'])->name('admin.servers.view.manage.transfer');
    Route::post('/view/{server:id}/delete', [Admin\ServersController::class, 'delete']);

    Route::patch('/view/{server:id}/details', [Admin\ServersController::class, 'setDetails']);
    Route::patch('/view/{server:id}/database', [Admin\ServersController::class, 'resetDatabasePassword']);

    Route::delete('/view/{server:id}/database/{database:id}/delete', [Admin\ServersController::class, 'deleteDatabase'])->name('admin.servers.view.database.delete');
    Route::delete('/view/{server:id}/mounts/{mount:id}', [Admin\ServersController::class, 'deleteMount'])
        ->name('admin.servers.view.mounts.delete');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nodes
|
*/
Route::group(['prefix' => 'nodes'], function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index'])->name('admin.nodes');
    Route::get('/new', [Admin\NodesController::class, 'create'])->name('admin.nodes.new');
    Route::get('/view/{node:id}', [Admin\Nodes\NodeViewController::class, 'index'])->name('admin.nodes.view');
    Route::get('/view/{node:id}/settings', [Admin\Nodes\NodeViewController::class, 'settings'])->name('admin.nodes.view.settings');
    Route::get('/view/{node:id}/configuration', [Admin\Nodes\NodeViewController::class, 'configuration'])->name('admin.nodes.view.configuration');
    Route::get('/view/{node:id}/allocation', [Admin\Nodes\NodeViewController::class, 'allocations'])->name('admin.nodes.view.allocation');
    Route::get('/view/{node:id}/servers', [Admin\Nodes\NodeViewController::class, 'servers'])->name('admin.nodes.view.servers');
    Route::get('/view/{node:id}/system-information', Admin\Nodes\SystemInformationController::class);

    Route::post('/new', [Admin\NodesController::class, 'store']);
    Route::post('/view/{node:id}/allocation', [Admin\NodesController::class, 'createAllocation']);
    Route::post('/view/{node:id}/allocation/remove', [Admin\NodesController::class, 'allocationRemoveBlock'])->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{node:id}/allocation/alias', [Admin\NodesController::class, 'allocationSetAlias'])->name('admin.nodes.view.allocation.setAlias');
    Route::post('/view/{node:id}/settings/token', Admin\NodeAutoDeployController::class)->name('admin.nodes.view.configuration.token');

    Route::post('/allserverrestart/{node:id}/', [Admin\NodesController::class, 'allServerRestart'])->name('admin.nodes.allserverrestart');
    Route::post('/reboot/{node:id}/', [Admin\NodesController::class, 'reboot'])->name('admin.nodes.reboot');
    Route::post('/hardreboot/{node:id}/', [Admin\NodesController::class, 'hardReboot'])->name('admin.nodes.hardreboot');
    Route::post('/shutdown/{node:id}/', [Admin\NodesController::class, 'shutdown'])->name('admin.nodes.shutdown');
    Route::post('/hardshutdown/{node:id}/', [Admin\NodesController::class, 'hardShutdown'])->name('admin.nodes.hardshutdown');

    Route::patch('/view/{node:id}/settings', [Admin\NodesController::class, 'updateSettings']);

    Route::delete('/view/{node:id}/delete', [Admin\NodesController::class, 'delete'])->name('admin.nodes.view.delete');
    Route::delete('/view/{node:id}/allocation/remove/{allocation:id}', [Admin\NodesController::class, 'allocationRemoveSingle'])->name('admin.nodes.view.allocation.removeSingle');
    Route::delete('/view/{node:id}/allocations', [Admin\NodesController::class, 'allocationRemoveMultiple'])->name('admin.nodes.view.allocation.removeMultiple');
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/mounts
|
*/
Route::group(['prefix' => 'mounts'], function () {
    Route::get('/', [Admin\MountController::class, 'index'])->name('admin.mounts');
    Route::get('/view/{mount:id}', [Admin\MountController::class, 'view'])->name('admin.mounts.view');

    Route::post('/', [Admin\MountController::class, 'create']);
    Route::post('/{mount:id}/eggs', [Admin\MountController::class, 'addEggs'])->name('admin.mounts.eggs');
    Route::post('/{mount:id}/nodes', [Admin\MountController::class, 'addNodes'])->name('admin.mounts.nodes');

    Route::patch('/view/{mount:id}', [Admin\MountController::class, 'update']);

    Route::delete('/{mount:id}/eggs/{egg_id}', [Admin\MountController::class, 'deleteEgg']);
    Route::delete('/{mount:id}/nodes/{node_id}', [Admin\MountController::class, 'deleteNode']);
});

/*
|--------------------------------------------------------------------------
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nests
|
*/
Route::group(['prefix' => 'nests'], function () {
    Route::get('/', [Admin\Nests\NestController::class, 'index'])->name('admin.nests');
    Route::get('/new', [Admin\Nests\NestController::class, 'create'])->name('admin.nests.new');
    Route::get('/view/{nest:id}', [Admin\Nests\NestController::class, 'view'])->name('admin.nests.view');
    Route::get('/egg/new', [Admin\Nests\EggController::class, 'create'])->name('admin.nests.egg.new');
    Route::get('/egg/{egg:id}', [Admin\Nests\EggController::class, 'view'])->name('admin.nests.egg.view');
    Route::get('/egg/{egg:id}/export', [Admin\Nests\EggShareController::class, 'export'])->name('admin.nests.egg.export');
    Route::get('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'view'])->name('admin.nests.egg.variables');
    Route::get('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'index'])->name('admin.nests.egg.scripts');

    Route::post('/new', [Admin\Nests\NestController::class, 'store']);
    Route::post('/import', [Admin\Nests\EggShareController::class, 'import'])->name('admin.nests.egg.import');
    Route::post('/egg/new', [Admin\Nests\EggController::class, 'store']);
    Route::post('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'store']);

    Route::put('/egg/{egg:id}', [Admin\Nests\EggShareController::class, 'update']);

    Route::patch('/view/{nest:id}', [Admin\Nests\NestController::class, 'update']);
    Route::patch('/egg/{egg:id}', [Admin\Nests\EggController::class, 'update']);
    Route::patch('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'update']);
    Route::patch('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'update'])->name('admin.nests.egg.variables.edit');

    Route::delete('/view/{nest:id}', [Admin\Nests\NestController::class, 'destroy']);
    Route::delete('/egg/{egg:id}', [Admin\Nests\EggController::class, 'destroy']);
    Route::delete('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| SubDomain Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/subdomain
|
*/
Route::group(['prefix' => 'subdomain'], function () {
    Route::get('/', [Admin\SubDomainController::class, 'index'])->name('admin.subdomain');
    Route::get('/new', [Admin\SubDomainController::class, 'new'])->name('admin.subdomain.new');
    Route::get('/edit/{id}', [Admin\SubDomainController::class, 'edit'])->name('admin.subdomain.edit');

    Route::post('/settings', [Admin\SubDomainController::class, 'settings'])->name('admin.subdomain.settings');
    Route::post('/create', [Admin\SubDomainController::class, 'create'])->name('admin.subdomain.create');
    Route::post('/update/{id}', [Admin\SubDomainController::class, 'update'])->name('admin.subdomain.update');

    Route::delete('/delete', [Admin\SubDomainController::class, 'delete'])->name('admin.subdomain.delete');
    Route::delete('/subdomain/delete', [Admin\SubDomainController::class, 'deleteSubdomain'])->name('admin.subdomain.subdomain.delete');
    Route::post('/sync', [Admin\SubDomainController::class, 'sync'])->name('admin.subdomain.sync');
});

/*
|--------------------------------------------------------------------------
| Mod Manager Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/mods
|
*/

Route::group(['prefix' => 'mods'], function () {
    Route::get('/', [Admin\ModsController::class, 'index'])->name('admin.mods');
    Route::get('/new', [Admin\ModsController::class, 'new'])->name('admin.mods.new');
    Route::get('/edit/{id}', [Admin\ModsController::class, 'edit'])->name('admin.mods.edit');

    Route::post('/settings', [Admin\ModsController::class, 'settings'])->name('admin.mods.settings');
    Route::post('/create', [Admin\ModsController::class, 'create'])->name('admin.mods.create');
    Route::post('/update/{id}', [Admin\ModsController::class, 'update'])->name('admin.mods.update');

    Route::delete('/delete', [Admin\ModsController::class, 'delete'])->name('admin.mods.delete');

    Route::get('/categories/new', [Admin\ModCategoriesController::class, 'new'])->name('admin.mods.categories.new');
    Route::get('/categories/edit/{id}', [Admin\ModCategoriesController::class, 'edit'])->name('admin.mods.categories.edit');

    Route::post('/categories/create', [Admin\ModCategoriesController::class, 'create'])->name('admin.mods.categories.create');
    Route::post('/categories/update/{id}', [Admin\ModCategoriesController::class, 'update'])->name('admin.mods.categories.update');

    Route::delete('/categories/delete', [Admin\ModCategoriesController::class, 'delete'])->name('admin.mods.categories.delete');

});

/*
|--------------------------------------------------------------------------
| Backup Manager Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/backups
|
*/

Route::group(['prefix' => 'backups'], function () {
    Route::get('/', [Admin\Iceline\BackupsController::class, 'index'])->name('admin.backups');

//    Route::get('/settings', 'Iceline\BackupSettingsController::class, 'index'])->name('admin.backups.settings');
//    Route::post('/settings', 'Iceline\BackupSettingsController::class, 'update'])->name('admin.backups.settings.update');
//    Route::post('/settings/update/{id}', 'Iceline\BackupSettingsController::class, 'updateServer'])->name('admin.backups.settings.updateServer');
//    Route::get('/settings/new', 'Iceline\BackupSettingsController::class, 'new'])->name('admin.backups.settings.new');
//    Route::get('/settings/edit/{id}', 'Iceline\BackupSettingsController::class, 'edit'])->name('admin.backups.settings.edit');
//    Route::post('/settings/create', 'Iceline\BackupSettingsController::class, 'create'])->name('admin.backups.settings.create');

    Route::get('/files', [Admin\Iceline\FileBackupsController::class, 'index'])->name('admin.backups.files');
    Route::get('/files/new', [Admin\Iceline\FileBackupsController::class, 'new'])->name('admin.backups.files.new');
    Route::post('/files/create', [Admin\Iceline\FileBackupsController::class, 'create'])->name('admin.backups.files.create');
    Route::delete('/files/delete', [Admin\Iceline\FileBackupsController::class, 'delete'])->name('admin.backups.file.delete');
    Route::get('/files/{id}/download', [Admin\Iceline\FileBackupsController::class, 'download'])->name('admin.backups.file.download');

    Route::get('/database', [Admin\Iceline\DatabaseBackupsController::class, 'index'])->name('admin.backups.database');
    Route::get('/database/new', [Admin\Iceline\DatabaseBackupsController::class, 'new'])->name('admin.backups.database.new');
    Route::post('/database/create', [Admin\Iceline\DatabaseBackupsController::class, 'create'])->name('admin.backups.database.create');
    Route::delete('/database/delete', [Admin\Iceline\DatabaseBackupsController::class, 'delete'])->name('admin.backups.database.delete');
    Route::get('/database/{id}/download', [Admin\Iceline\DatabaseBackupsController::class, 'download'])->name('admin.backups.database.download');
});

Route::group(['prefix' => 'sysbackups'], function () {
    Route::get('/', [Admin\Iceline\SystemBackupsController::class, 'index'])->name('admin.sysbackups');
    Route::get('/download', [Admin\Iceline\SystemBackupsController::class, 'download'])->name('admin.sysbackups.download');
    Route::get('/link', [Admin\Iceline\SystemBackupsController::class, 'link'])->name('admin.sysbackups.link');
});


/*
|--------------------------------------------------------------------------
| Staff Controller Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /staff
|
*/
Route::post('/staff/update/{id}', [Admin\StaffController::class, 'update'])->name('admin.staff.update');

/*
|--------------------------------------------------------------------------
| Ip Changer Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/ipchange
|
*/
Route::group(['prefix' => 'ipchange'], function () {
    Route::get('/', [Admin\IpChangerController::class, 'index'])->name('admin.ipchange');

    Route::post('/change', [Admin\IpChangerController::class, 'change'])->name('admin.ipchange.change');
    Route::post('/global', [Admin\IpChangerController::class, 'global'])->name('admin.ipchange.global');
});

/*
|--------------------------------------------------------------------------
| Auto Allocation Adder Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/autoallocation
|
*/
Route::group(['prefix' => 'autoallocation'], function () {
    Route::get('/', [Admin\AutoAllocationController::class, 'index'])->name('admin.autoallocation');
    Route::get('/new', [Admin\AutoAllocationController::class, 'new'])->name('admin.autoallocation.new');
    Route::get('/edit/{id}', [Admin\AutoAllocationController::class, 'edit'])->name('admin.autoallocation.edit');

    Route::post('/create', [Admin\AutoAllocationController::class, 'create'])->name('admin.autoallocation.create');
    Route::post('/update/{id}', [Admin\AutoAllocationController::class, 'update'])->name('admin.autoallocation.update');
    Route::post('/apply', [Admin\AutoAllocationController::class, 'apply'])->name('admin.autoallocation.apply');

    Route::delete('/delete', [Admin\AutoAllocationController::class, 'delete'])->name('admin.autoallocation.delete');
});

/*
|--------------------------------------------------------------------------
| Permission Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/permissions
|
*/
Route::group(['prefix' => 'permissions'], function () {
    Route::get('/', [Admin\PermissionController::class, 'index'])->name('admin.permissions.index');
    Route::get('/new', [Admin\PermissionController::class, 'new'])->name('admin.permissions.new');
    Route::get('/edit/{permission}', [Admin\PermissionController::class, 'edit'])->name('admin.permissions.edit');

    Route::get('/delete/{id}',[Admin\PermissionController::class, 'destroy']);

    Route::post('/new', [Admin\PermissionController::class, 'create']);
    Route::post('/edit/{permission}', [Admin\PermissionController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| IPChecker Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/ipchecker
|
*/
Route::group(['prefix' => 'ipchecker'], function () {
    Route::get('/', [Admin\IPCheckerController::class, 'index'])->name('admin.ipchecker');
    Route::post('/check', [Admin\IPCheckerController::class, 'check'])->name('admin.ipchecker.check');
    Route::post('/check_ips', [Admin\IPCheckerController::class, 'checkIPs'])->name('admin.ipchecker.checkIPs');
});

/*
|--------------------------------------------------------------------------
| IP Address Manager Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/addresses
|
*/
Route::group(['prefix' => 'addresses'], function () {
    Route::get('/', [Admin\Iceline\IPAddressController::class, 'index'])->name('admin.addresses');
    Route::get('/logs', [Admin\Iceline\IPAddressController::class, 'logs'])->name('admin.addresses.logs');

    Route::group(['prefix' => 'change'], function () {
        Route::get('/', [Admin\Iceline\IPAddressController::class, 'changeIndex'])->name('admin.addresses.changeIndex');

        Route::post('/change', [Admin\Iceline\IPAddressController::class, 'change'])->name('admin.addresses.change');
        Route::post('/global', [Admin\Iceline\IPAddressController::class, 'global'])->name('admin.addresses.change.global');
    });

    Route::group(['prefix' => 'reserve'], function () {
        Route::get('/', [Admin\Iceline\IPAddressController::class, 'reserve'])->name('admin.addresses.reserve');

        Route::post('/add', [Admin\Iceline\IPAddressController::class, 'addReserve'])->name('admin.addresses.reserve.add');
        Route::get('/remove', [Admin\Iceline\IPAddressController::class, 'removeReserve'])->name('admin.addresses.reserve.remove');
    });
});

/*
|--------------------------------------------------------------------------
| Egg Changer Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/eggchanger
|
*/
Route::group(['prefix' => 'eggchanger'], function () {
    Route::get('/', [Admin\EggChangerController::class, 'index'])->name('admin.eggchanger');

    Route::post('/availables', [Admin\EggChangerController::class, 'availables'])->name('admin.eggchanger.availables');
    Route::post('/defaults', [Admin\EggChangerController::class, 'defaults'])->name('admin.eggchanger.defaults');

    Route::post('/{server_id}/availables', [Admin\EggChangerController::class, 'serverAvailables'])->name('admin.eggchanger.server.availables');
});

/*
|--------------------------------------------------------------------------
| Ip Changer Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/ipchange
|
*/
Route::group(['prefix' => 'ipchange'], function () {
    Route::get('/', [Admin\IpChangerController::class, 'index'])->name('admin.ipchange');

    Route::post('/change', [Admin\IpChangerController::class, 'change'])->name('admin.ipchange.change');
    Route::post('/global', [Admin\IpChangerController::class, 'global'])->name('admin.ipchange.global');
});

/*
|--------------------------------------------------------------------------
| Filter Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/filters
|
*/
Route::group(['prefix' => '/filters'], function () {
    Route::get('/', [Admin\FilterController::class, 'index'])->name('admin.filters');
    Route::get('/{id}', [Admin\FilterController::class, 'edit'])->name('admin.filters.edit');

	Route::post('/save', [Admin\FilterController::class, 'save'])->name('admin.filters.save');
    Route::post('/store', [Admin\FilterController::class, 'store'])->name('admin.filters.store');
	Route::post('/push', [Admin\FilterController::class, 'push'])->name('admin.filters.push');
    Route::post('/{id}', [Admin\FilterController::class, 'update'])->name('admin.filters.update');

    Route::delete('/delete', [Admin\FilterController::class, 'delete'])->name('admin.filters.delete');
});

/*
|--------------------------------------------------------------------------
| Popular Servers Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/players
|
*/
Route::group(['prefix' => '/players'], function () {
    Route::get('/', [Admin\PopularServersController::class, 'index'])->name('admin.players');
});

/*
|--------------------------------------------------------------------------
| Alert Manager Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/alert
|
*/
Route::group(['prefix' => 'alert'], function () {
    Route::get('/', [Admin\AlertController::class, 'index'])->name('admin.alert');

    Route::post('/create', [Admin\AlertController::class, 'create'])->name('admin.alert.create');
    Route::post('/edit/{id}', [Admin\AlertController::class, 'edit'])->name('admin.alert.edit');

    Route::delete('/delete', [Admin\AlertController::class, 'delete'])->name('admin.alert.delete');
});
