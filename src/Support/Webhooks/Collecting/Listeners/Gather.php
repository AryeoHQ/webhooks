<?php

declare(strict_types=1);

namespace Support\Webhooks\Collecting\Listeners;

use Support\Events\Log\Envelopes\Envelope;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;
use Support\Webhooks\Subscriptions\Subscription;

// TODO: Isn't it the package consumers responsibility to implement this?
class Gather
{
    public function handle(NeedsEnvelopes $event): void
    {
        Subscription::receiving($event->relay->log->type)->active()
            ->each(function (Subscription $subscription) use ($event): void {
                $event->add(
                    Envelope::make(recipient: $subscription, version: $subscription->version),
                );
            });
    }
}
