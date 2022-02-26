<?php

namespace App;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Host
{
    public int $id;
    public Collection $domains;
    public string $host;
    public int $port;
    public ?Certificate $certificate = null;
    public bool $forceSsl;

    public function __construct(array $data)
    {
        $this->id = Arr::get($data, 'id');
        $this->domains = collect(Arr::get($data, 'domain_names'));
        $this->host = Arr::get($data, 'forward_host');
        $this->port = Arr::get($data, 'forward_port');
        $this->forceSsl = (bool) Arr::get($data, 'ssl_forced');
        if ($certificate = Arr::get($data, 'certificate')) {
            $this->certificate = new Certificate($certificate);
        }
    }

    public static function from(array $data): Host
    {
        return new self($data);
    }
}
