<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Listeners;

use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Events\Log\Deliveries\Status\Status as DeliveryStatus;
use Support\Webhooks\Subscriptions\Status\Status;
use Support\Webhooks\Subscriptions\Subscription;

class AutoDisable
{
    public function handle(Failed $event): void
    {
        $threshold = (int) config('webhooks.failure.threshold');

        if ($threshold === 0) {
            return;
        }

        $recipient = $event->delivery->recipient;

        if (! $recipient instanceof Subscription) {
            return;
        }

        if ($recipient->status->enum !== Status::Active) {
            return;
        }

        $lastSuccess = Delivery::query() // @phpstan-ignore staticMethod.dynamicCall
            ->where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey())
            ->where('status', DeliveryStatus::Succeeded)
            ->max('updated_at');

        $query = Delivery::query()
            ->where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey())
            ->where('status', DeliveryStatus::Failed);

        if ($lastSuccess !== null) {
            $query->where('updated_at', '>', $lastSuccess);
        }

        $consecutiveFailures = $query->count(); // @phpstan-ignore staticMethod.dynamicCall

        if ($consecutiveFailures >= $threshold) {
            $recipient->status->disable()->now();
        }
    }
}
