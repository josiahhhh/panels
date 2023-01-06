<?php

namespace Pterodactyl\Console;

use Pterodactyl\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pterodactyl\Console\Commands\Iceline\DeleteBackupsCommand;
use Pterodactyl\Console\Commands\Maintenance\DeleteAlertsCommand;
use Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand;
use Pterodactyl\Console\Commands\Iceline\DeleteFailedBackupsCommand;
use Pterodactyl\Console\Commands\Iceline\FivemBlacklistCheckCommand;
use Pterodactyl\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Execute scheduled commands for servers every minute, as if there was a normal cron running.
        $schedule->command(ProcessRunnableCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(CleanServiceBackupFilesCommand::class)->daily();

        if (config('backups.prune_age')) {
            // Every 30 minutes, run the backup pruning command so that any abandoned backups can be deleted.
            $schedule->command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes();
        }

        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
        }

        // Clean up backups outside of their retention period.
        //
        // NOTE: ->onOneServer() is required to prevent multiple Laravel
        //   instances trying to prune the same servers all at once.
        $schedule->command(DeleteBackupsCommand::class)->everyThirtyMinutes()->onOneServer();

        // Clean up failed backups that are older then 72 hours.
        $schedule->command(DeleteFailedBackupsCommand::class)->everyTwoHours()->onOneServer();

        // Run periodic banned IP check
        $schedule->command(FivemBlacklistCheckCommand::class)->everyFiveMinutes()->onOneServer();

        // Alert check
        $schedule->command(DeleteAlertsCommand::class)->hourly();
    }
}
