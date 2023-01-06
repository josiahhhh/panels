<?php

namespace Pterodactyl\Providers\Iceline;

use Illuminate\Support\ServiceProvider;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Iceline\DatabaseBackupManager;

class DatabaseBackupProvider extends ServiceProvider {
    /**
     * Register the database backup manager.
     */
    public function register() {
        $this->app->singleton(DatabaseBackupManager::class, function ($app) {
            return new DatabaseBackupManager($app);
        });
    }

    /**
     * @return string[]
     */
    public function provides() {
        return [DatabaseBackupManager::class];
    }
}
