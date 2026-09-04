<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Listeners;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Events\Log\Deliveries\Status\Status as DeliveryStatus;
use Support\Events\Log\Envelopes\Envelope;
use Support\Webhooks\Subscriptions\Status\Status;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(AutoDisable::class)]
final class AutoDisableTest extends TestCase
{
    #[Test]
    public function it_disables_a_subscription_after_reaching_the_threshold(): void
    {
        config(['webhooks.failure.threshold' => 3]);

        $subscription = Subscription::factory()->for(Subscriber::factory())->active()->create();

        $this->createFailedDeliveries($subscription, 3);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($subscription))
        );

        $this->assertSame(Status::Disabled, $subscription->refresh()->status->enum);
    }

    #[Test]
    public function it_does_not_disable_below_the_threshold(): void
    {
        config(['webhooks.failure.threshold' => 3]);

        $subscription = Subscription::factory()->for(Subscriber::factory())->active()->create();

        $this->createFailedDeliveries($subscription, 2);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($subscription))
        );

        $this->assertSame(Status::Active, $subscription->refresh()->status->enum);
    }

    #[Test]
    public function a_success_resets_the_consecutive_count(): void
    {
        config(['webhooks.failure.threshold' => 3]);

        $subscription = Subscription::factory()->for(Subscriber::factory())->active()->create();

        $this->createFailedDeliveries($subscription, 2);

        Delivery::factory()->webhook()->createQuietly([
            'envelope' => Envelope::make(recipient: $subscription),
            'status' => DeliveryStatus::Succeeded,
        ]);

        $this->createFailedDeliveries($subscription, 2);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($subscription))
        );

        $this->assertSame(Status::Active, $subscription->refresh()->status->enum);
    }

    #[Test]
    public function it_skips_when_threshold_is_zero(): void
    {
        config(['webhooks.failure.threshold' => 0]);

        $subscription = Subscription::factory()->for(Subscriber::factory())->active()->create();

        $this->createFailedDeliveries($subscription, 20);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($subscription))
        );

        $this->assertSame(Status::Active, $subscription->refresh()->status->enum);
    }

    #[Test]
    public function it_skips_non_subscription_recipients(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly([
            'status' => DeliveryStatus::Failed,
        ]);

        (new AutoDisable)->handle(new Failed($delivery));

        $this->assertTrue(true);
    }

    #[Test]
    public function it_skips_already_inactive_subscriptions(): void
    {
        config(['webhooks.failure.threshold' => 1]);

        $subscription = Subscription::factory()->for(Subscriber::factory())->inactive()->create();

        $this->createFailedDeliveries($subscription, 5);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($subscription))
        );

        $this->assertSame(Status::Inactive, $subscription->refresh()->status->enum);
    }

    private function createFailedDeliveries(Subscription $subscription, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Delivery::factory()->webhook()->createQuietly([
                'envelope' => Envelope::make(recipient: $subscription),
                'status' => DeliveryStatus::Failed,
            ]);
        }
    }

    private function latestDelivery(Subscription $subscription): Delivery
    {
        return Delivery::query()
            ->where('recipient_type', $subscription->getMorphClass())
            ->where('recipient_id', $subscription->getKey())
            ->latest()
            ->first();
    }
}
