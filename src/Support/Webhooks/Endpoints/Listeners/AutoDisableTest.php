<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints\Listeners;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Events\Log\Deliveries\Status\Status as DeliveryStatus;
use Support\Events\Log\Envelopes\Envelope;
use Support\Webhooks\Endpoints\Endpoint;
use Support\Webhooks\Endpoints\Status\Status;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(AutoDisable::class)]
final class AutoDisableTest extends TestCase
{
    #[Test]
    public function it_disables_an_endpoint_after_reaching_the_threshold(): void
    {
        config(['webhooks.endpoints.failures.threshold' => 3]);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->active()->create();

        $this->createFailedDeliveries($endpoint, 3);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($endpoint))
        );

        $this->assertSame(Status::Disabled, $endpoint->refresh()->status->enum);
    }

    #[Test]
    public function it_does_not_disable_below_the_threshold(): void
    {
        config(['webhooks.endpoints.failures.threshold' => 3]);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->active()->create();

        $this->createFailedDeliveries($endpoint, 2);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($endpoint))
        );

        $this->assertSame(Status::Active, $endpoint->refresh()->status->enum);
    }

    #[Test]
    public function a_success_resets_the_consecutive_count(): void
    {
        config(['webhooks.endpoints.failures.threshold' => 3]);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->active()->create();

        $this->createFailedDeliveries($endpoint, 2);

        Delivery::factory()->webhook()->createQuietly([
            'envelope' => Envelope::make(recipient: $endpoint),
            'status' => DeliveryStatus::Succeeded,
        ]);

        $this->createFailedDeliveries($endpoint, 2);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($endpoint))
        );

        $this->assertSame(Status::Active, $endpoint->refresh()->status->enum);
    }

    #[Test]
    public function it_skips_when_threshold_is_zero(): void
    {
        config(['webhooks.endpoints.failures.threshold' => 0]);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->active()->create();

        $this->createFailedDeliveries($endpoint, 20);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($endpoint))
        );

        $this->assertSame(Status::Active, $endpoint->refresh()->status->enum);
    }

    #[Test]
    public function it_skips_non_endpoint_recipients(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly([
            'status' => DeliveryStatus::Failed,
        ]);

        (new AutoDisable)->handle(new Failed($delivery));

        $this->assertTrue(true);
    }

    #[Test]
    public function it_skips_already_inactive_endpoints(): void
    {
        config(['webhooks.endpoints.failures.threshold' => 1]);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->inactive()->create();

        $this->createFailedDeliveries($endpoint, 5);

        (new AutoDisable)->handle(
            new Failed($this->latestDelivery($endpoint))
        );

        $this->assertSame(Status::Inactive, $endpoint->refresh()->status->enum);
    }

    private function createFailedDeliveries(Endpoint $endpoint, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Delivery::factory()->webhook()->createQuietly([
                'envelope' => Envelope::make(recipient: $endpoint),
                'status' => DeliveryStatus::Failed,
            ]);
        }
    }

    private function latestDelivery(Endpoint $endpoint): Delivery
    {
        return Delivery::query()
            ->where('recipient_type', $endpoint->getMorphClass())
            ->where('recipient_id', $endpoint->getKey())
            ->latest()
            ->first();
    }
}
