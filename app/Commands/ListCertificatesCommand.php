<?php

namespace App\Commands;

use App\Certificate;
use App\Client;
use LaravelZero\Framework\Commands\Command;

class ListCertificatesCommand extends Command
{
    /**
     * The signature of the command.
     * @var string
     */
    protected $signature = 'cert:list';

    /**
     * The description of the command.
     * @var string
     */
    protected $description = 'List certificates';

    /**
     * Execute the console command.
     * @return int
     */
    public function handle(Client $client)
    {
        $certificates = $client->getCertificates();
        if ($certificates->isEmpty()) {
            $this->info('No certificates found');
        } else {
            $this->table(['Name', 'Expires on'], $certificates->map(fn(Certificate $certificate) => [
                $certificate->name,
                $certificate->getExpiryLabel()
            ]));
        }
        return self::SUCCESS;
    }
}
