<?php

namespace App\Commands;

use App\Client;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class AddHostCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'hosts:add           {domains*                    : Domain names}
                                                {--host=host.docker.internal : Hostname or docker-compose service}
                                                {--port=80                   : Web server port}
                                                {--https                     : Create self-signed SSL certificate}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Add a host to Nginx Proxy Manager';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
        $domains = $this->argument('domains');
        $host = $this->option('host');
        $port = $this->option('port');
        $https = $this->option('https');
        try {
            $client->createHost($domains, $host, $port, $https);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info('Host created');
        return self::SUCCESS;
    }
}
