<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Webhooks\Subscriptions\Listeners\AutoDisable;

class Provider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootListeners();
        $this->bootMigrations();
    }

    private function bootListeners(): void
    {
        Event::listen(Failed::class, AutoDisable::class);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
    }
}
