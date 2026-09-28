<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints\Listeners;

use Support\Events\Log\Deliveries\Builder;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Events\Log\Deliveries\Status\Status as DeliveryStatus;
use Support\Webhooks\Endpoints\Endpoint;
use Support\Webhooks\Endpoints\Status\Status;

class AutoDisable
{
    private int $threshold {
        get => $this->threshold ??= (int) config('webhooks.endpoints.failures.threshold');
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
     * @phpstan-assert-if-true Endpoint $recipient
     */
    private function shouldHandle(mixed $recipient): bool
    {
        return $this->threshold !== 0
            && $recipient instanceof Endpoint
            && $recipient->status->enum === Status::Active;
    }

    private function consecutiveFailuresFor(Endpoint $endpoint): int
    {
        $lastSuccess = Delivery::query() // @phpstan-ignore staticMethod.dynamicCall
            ->whereMorphedTo('recipient', $endpoint)
            ->where('status', DeliveryStatus::Succeeded)
            ->max('updated_at');

        return Delivery::query() // @phpstan-ignore staticMethod.dynamicCall
            ->whereMorphedTo('recipient', $endpoint)
            ->where('status', DeliveryStatus::Failed)
            ->when($lastSuccess, fn (Builder $query) => $query->where('updated_at', '>', $lastSuccess))
            ->count();
    }
}
