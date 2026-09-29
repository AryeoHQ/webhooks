<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints;

use Illuminate\Support\Str;

trait GeneratesSecret
{
    public function initializeGeneratesSecret(): void
    {
        $this->attributes['secret'] ??= Str::random(64);
    }
}
