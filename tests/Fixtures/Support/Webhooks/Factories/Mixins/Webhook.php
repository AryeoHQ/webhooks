<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Webhooks\Factories\Mixins;

use Closure;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Relays\Relay;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Events\Updated;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;

/** @mixin \Illuminate\Database\Eloquent\Factories\Factory<\Illuminate\Database\Eloquent\Model> */
final class Webhook
{
    /** @return Closure(class-string<\Support\Events\Log\Transports\Contracts\Transport> $transport=): \Illuminate\Database\Eloquent\Factories\Factory<\Illuminate\Database\Eloquent\Model> */
    public function webhook(): Closure
    {
        return function (string $transport = \Support\Webhooks\Contracts\Webhook::class) {
            return match ($this->modelName()) {
                Log::class => $this->state(fn (): array => [
                    'event' => new Updated(Subscriber::factory()->create()),
                ]),
                Relay::class => $this
                    ->for(Log::factory()->webhook(), 'log')
                    ->state(['transport' => $transport]),
                Delivery::class => $this
                    ->for(Relay::factory()->webhook($transport), 'relay')
                    ->state(fn (): array => [
                        'envelope' => Envelope::make(
                            recipient: Subscription::factory()->for(Subscriber::factory())->create(),
                        ),
                    ]),
                DeliveryAttempt::class => $this
                    ->for(Delivery::factory()->webhook($transport), 'delivery'),
                default => $this,
            };
        };
    }
}
