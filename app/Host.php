<?php

namespace App;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Host
{
    public int $id;

    /** @var \Illuminate\Support\Collection<int, string> */
    public Collection $domains;

    public string $host;

    public int $port;

    public ?Certificate $certificate = null;

    public bool $forceSsl;

    public bool $enabled;

    public function __construct(array $data)
    {
        $this->id = Arr::get($data, 'id');
        /** @var array<int, string> $domains */
        $domains = Arr::get($data, 'domain_names');
        $this->domains = collect($domains);
        $this->host = Arr::get($data, 'forward_host');
        $this->port = Arr::get($data, 'forward_port');
        $this->forceSsl = (bool) Arr::get($data, 'ssl_forced');
        $this->enabled = (bool) Arr::get($data, 'enabled');
        if ($certificate = Arr::get($data, 'certificate')) {
            $this->certificate = new Certificate($certificate);
        }
    }

    public static function from(array $data): Host
    {
        return new self($data);
    }
}
