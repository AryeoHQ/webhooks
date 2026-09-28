<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Events\Log\Transportables\Events\Deleting as TransportableDeleting;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Endpoint;
use Support\Webhooks\Endpoints\Events\Deleting as EndpointDeleting;
use Support\Webhooks\Topics\Listeners\Cleanup;
use Support\Webhooks\Topics\Listeners\Detach;
use Support\Webhooks\Topics\Topic;

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
            return $transportable->belongsToMany(Endpoint::using(), 'webhook_endpoint_topics', 'event_log_transportable_id', 'webhook_endpoint_id')
                ->using(Topic::using())
                ->withTimestamps();
        });
    }

    private function bootListeners(): void
    {
        Event::listen(EndpointDeleting::class, Cleanup::class);
        Event::listen(TransportableDeleting::class, Detach::class);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
    }
}
