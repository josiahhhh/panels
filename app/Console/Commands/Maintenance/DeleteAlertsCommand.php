<?php

namespace Pterodactyl\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:maintenance:alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired alerts from the system.';

    /**
     * @return void
     */
    public function handle()
    {
        foreach (DB::table('alerts')->where('delete_when_expired', '=', 2)->get() as $alert) {
            if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($alert->expire_at))) {
                DB::table('alerts')->where('id', '=', $alert->id)->delete();
            }
        }
    }
}
