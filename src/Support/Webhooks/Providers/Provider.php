<?php

declare(strict_types=1);

namespace Support\Webhooks\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Webhooks\Sending\Events\NeedsSent;
use Support\Webhooks\Sending\Listeners\Deliver;
use Support\Webhooks\Subscriptions\Listeners\AutoDisable;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(): void
    {
        $this->bootListeners();
        $this->bootMigrations();
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/webhooks.php', 'webhooks');
    }

    private function bootListeners(): void
    {
        Event::listen(NeedsSent::class, Deliver::class);
        Event::listen(Failed::class, AutoDisable::class);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Subscriptions/Migrations');
    }
}
