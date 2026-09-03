<?php

declare(strict_types=1);

namespace Support\Webhooks\Collecting\Events;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Relays\Relay;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(NeedsEnvelopes::class)]
final class NeedsEnvelopesTest extends TestCase
{
    #[Test]
    public function it_exposes_the_relay(): void
    {
        $relay = Relay::factory()->webhook()->createQuietly();

        $event = new NeedsEnvelopes($relay);

        $this->assertTrue($relay->is($event->relay));
    }

    #[Test]
    public function it_collects_envelopes(): void
    {
        $relay = Relay::factory()->webhook()->createQuietly();
        $subscription = Subscription::factory()->for(Subscriber::factory(), 'subscriber')->create();

        $event = new NeedsEnvelopes($relay);
        $event->add(Envelope::make(recipient: $subscription));

        $this->assertCount(1, $event->envelopes);
        $this->assertTrue($subscription->is($event->envelopes->first()->recipient));
    }
}
