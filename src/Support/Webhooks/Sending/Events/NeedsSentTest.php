<?php

declare(strict_types=1);

namespace Support\Webhooks\Sending\Events;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Tests\TestCase;

#[CoversClass(NeedsSent::class)]
final class NeedsSentTest extends TestCase
{
    #[Test]
    public function it_exposes_the_delivery(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        $this->assertTrue($delivery->is($event->delivery));
    }

    #[Test]
    public function it_exposes_the_delivery_id_as_the_idempotency_key(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        $this->assertSame($delivery->id, $event->idempotencyKey);
    }

    #[Test]
    public function it_records_a_result(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);
        $event->result('response');

        $this->assertSame('response', $event->result);
    }
}
