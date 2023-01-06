<?php

namespace Pterodactyl\Console\Commands\Iceline;

use Pterodactyl\Models\Server;
use Illuminate\Console\Command;
use Pterodactyl\Services\Servers\FilterService;

class ApplyFirewallRulesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'p:iceline:firewall';

    /**
     * @var string
     */
    protected $description = 'Apply firewall rules in all not added server';

    /**
     * @return void
     */
    public function handle()
    {
        $servers = Server::query()->get();

        foreach ($servers as $key => $server) {
            if (!is_null($server->rules) && count(json_decode($server->rules)) > 0) {
                unset($servers[$key]);
            }
        }

        $bar = $this->output->createProgressBar(count($servers));
        $bar->start();

        foreach ($servers as $server) {
            $add = FilterService::createRules($server);
            if (!$add['success']) {
                $this->error(print_r($add['errors'], true));
            }

            $bar->advance();
            $bar->display();
        }

        $bar->finish();
        $this->line('All rules successfully added.');
    }
}
