<?php

declare(strict_types=1);

namespace Support\Webhooks\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Webhooks\Sending\Events\NeedsSent;
use Support\Webhooks\Sending\Listeners\Deliver;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(): void
    {
        $this->bootListeners();
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/webhooks.php', 'webhooks');
    }

    private function bootListeners(): void
    {
        Event::listen(NeedsSent::class, Deliver::class);
    }
}
