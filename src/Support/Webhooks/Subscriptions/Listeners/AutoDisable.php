<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Listeners;

use Support\Events\Log\Deliveries\Builder;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Events\Log\Deliveries\Status\Status as DeliveryStatus;
use Support\Webhooks\Subscriptions\Status\Status;
use Support\Webhooks\Subscriptions\Subscription;

class AutoDisable
{
    private int $threshold {
        get => $this->threshold ??= (int) config('webhooks.subscriptions.failures.threshold');
    }

    public function handle(Failed $event): void
    {
        $recipient = $event->delivery->recipient;

        if (! $this->shouldHandle($recipient)) {
            return;
        }

        if ($this->consecutiveFailuresFor($recipient) >= $this->threshold) {
            $recipient->status->disable()->now();
        }
    }

    /**
     * @phpstan-assert-if-true Subscription $recipient
     */
    private function shouldHandle(mixed $recipient): bool
    {
        return $this->threshold !== 0
            && $recipient instanceof Subscription
            && $recipient->status->enum === Status::Active;
    }

    private function consecutiveFailuresFor(Subscription $subscription): int
    {
        $lastSuccess = Delivery::query() // @phpstan-ignore staticMethod.dynamicCall
            ->whereMorphedTo('recipient', $subscription)
            ->where('status', DeliveryStatus::Succeeded)
            ->max('updated_at');

        return Delivery::query() // @phpstan-ignore staticMethod.dynamicCall
            ->whereMorphedTo('recipient', $subscription)
            ->where('status', DeliveryStatus::Failed)
            ->when($lastSuccess, fn (Builder $query) => $query->where('updated_at', '>', $lastSuccess))
            ->count();
    }
}
