<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Endpoint;
use Support\Webhooks\Subscriptions\Listeners\Cleanup;
use Support\Webhooks\Subscriptions\Listeners\Detach;
use Support\Webhooks\Subscriptions\Subscription;

class Provider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootRelationships();
        $this->bootListeners();
        $this->bootMigrations();
    }

    private function bootRelationships(): void
    {
        Transportable::resolveRelationUsing('webhookEndpoints', function (Transportable $transportable) {
            return $transportable->belongsToMany(Endpoint::using(), 'webhook_subscriptions', 'event_log_transportable_id', 'webhook_endpoint_id')
                ->using(Subscription::using())
                ->as('subscription')
                ->withTimestamps();
        });
    }

    private function bootListeners(): void
    {
        $this->app->booted(function (): void {
            Event::listen(data_get(resolve(Endpoint::using())->dispatchesEvents(), 'deleting'), Cleanup::class);
            Event::listen(data_get(resolve(Transportable::using())->dispatchesEvents(), 'deleting'), Detach::class);
        });
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
    }
}
