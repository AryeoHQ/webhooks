<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Events\Log\Transportables\Events\Deleting as TransportableDeleting;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Subscriptions\Events\Deleting as SubscriptionDeleting;
use Support\Webhooks\Subscriptions\Subscription;
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
        Transportable::resolveRelationUsing('webhookSubscriptions', function (Transportable $transportable) {
            return $transportable->belongsToMany(Subscription::using(), 'webhook_subscription_topics', 'event_log_transportable_id', 'webhook_subscription_id')
                ->using(Topic::using())
                ->withTimestamps();
        });
    }

    private function bootListeners(): void
    {
        Event::listen(SubscriptionDeleting::class, Cleanup::class);
        Event::listen(TransportableDeleting::class, Detach::class);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
    }
}
