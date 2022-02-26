<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class Certificate
{
    public ?int $id = null;
    public string $name;
    public Carbon $expires_at;

    public function __construct(array $data)
    {
        $this->id = Arr::get($data, 'id');
        $this->name = Arr::get($data, 'nice_name');
        $this->expires_at = Carbon::make(Arr::get($data, 'expires_on'));
    }

    public function getExpiryLabel(): string
    {
        return sprintf('%s (%s)', $this->expires_at->isoFormat('LLLL'), $this->expires_at->diffForHumans());
    }
}
