<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions;

use Illuminate\Support\Str;

trait GeneratesSecret
{
    public static function bootGeneratesSecret(): void
    {
        static::creating(fn (self $model) => $model->secret ??= Str::random(64));
    }
}
