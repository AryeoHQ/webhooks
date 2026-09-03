<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Webhooks\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;
use Tests\Fixtures\Support\Webhooks\Factories\Mixins\Webhook;

final class Provider extends ServiceProvider
{
    public function boot(): void
    {
        Factory::mixin(new Webhook);
    }
}
