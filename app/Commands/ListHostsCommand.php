<?php

namespace App\Commands;

use App\Client;
use App\Host;
use LaravelZero\Framework\Commands\Command;

class ListHostsCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'hosts:list';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List hosts managed by Nginx Proxy Manager';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
        $hosts = $client->getHosts();
        $this->table(['Domains', 'Host', 'Port', 'SSL', 'Enabled'], $hosts->map(function(Host $host) {
            return [
                $host->domains->join(' '),
                $host->host,
                $host->port,
                $host->certificate ? '<fg=green>✓</>' : '<fg=red>✗</>',
                $host->enabled ? '<fg=green>✓</>' : '<fg=red>✗</>',
            ];
        }));

        return self::SUCCESS;
    }
}
