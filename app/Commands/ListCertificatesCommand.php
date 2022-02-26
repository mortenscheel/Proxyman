<?php

namespace App\Commands;

use App\Certificate;
use App\Client;
use LaravelZero\Framework\Commands\Command;

class ListCertificatesCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'certs:list';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List certificates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
        $certificates = $client->getCertificates();
        $this->table(['Name', 'Expires on'], $certificates->map(function(Certificate $certificate) {
            return [$certificate->name, $certificate->getExpiryLabel()];
        }));
        return self::SUCCESS;
    }
}
