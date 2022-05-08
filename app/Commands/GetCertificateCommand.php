<?php

namespace App\Commands;

use App\Certificate;
use App\Client;
use LaravelZero\Framework\Commands\Command;

class GetCertificateCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cert:get {name?   : Name of certificate (optional)}
                                     {--write : Write certificate files to disk (current folder)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get SSL certificate';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
        $certificates = $client->getCertificates()->mapWithKeys(fn(Certificate $cert) => [$cert->name => $cert]);
        $name = $this->argument('name');
        if (!$name) {
            $name = $this->choice('Select certificates', $certificates->keys()->toArray());
        }
        $certificate = $certificates->get($name);
        if (!$certificate) {
            $this->error("Certificate not found");
            return self::FAILURE;
        }
        if ($this->option('write')) {
            file_put_contents(getcwd() . "/$certificate->name.key", $certificate->key);
            file_put_contents(getcwd() . "/$certificate->name.crt", $certificate->cert);
            $this->info("$certificate->name.key and $certificate->name.crt saved in current folder.");
        }
        else {
            $this->getOutput()->writeln([
                "<fg=yellow>$certificate->name.key</>",
                "<fg=white>$certificate->key</>",
                "<fg=yellow>$certificate->name.crt</>",
                "<fg=white>$certificate->cert</>"
            ]);
        }

        return self::SUCCESS;
    }
}
