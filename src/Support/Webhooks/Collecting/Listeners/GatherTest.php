<?php

declare(strict_types=1);

namespace Support\Webhooks\Collecting\Listeners;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Relays\Relay;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Gather::class)]
final class GatherTest extends TestCase
{
    #[Test]
    public function it_adds_an_envelope_per_active_subscription(): void
    {
        $relay = Relay::factory()->webhook()->createQuietly();

        $subscriber = Subscriber::factory()->create();

        $subA = Subscription::factory()->for($subscriber)->active()->create(['event' => $relay->log->type]);
        $subB = Subscription::factory()->for($subscriber)->active()->create(['event' => $relay->log->type]);
        Subscription::factory()->for($subscriber)->inactive()->create(['event' => $relay->log->type]);

        $event = new NeedsEnvelopes($relay);

        (new Gather)->handle($event);

        $this->assertCount(2, $event->envelopes);

        $recipientIds = $event->envelopes->map(fn (Envelope $e) => $e->recipient->getKey())->all();
        $this->assertContains($subA->id, $recipientIds);
        $this->assertContains($subB->id, $recipientIds);
    }

    #[Test]
    public function it_adds_no_envelopes_when_there_are_no_subscriptions(): void
    {
        $relay = Relay::factory()->webhook()->createQuietly();

        $event = new NeedsEnvelopes($relay);

        (new Gather)->handle($event);

        $this->assertCount(0, $event->envelopes);
    }

    #[Test]
    public function it_passes_null_version_through_to_the_envelope(): void
    {
        $relay = Relay::factory()->webhook()->createQuietly();

        Subscription::factory()->for(Subscriber::factory())->create([
            'event' => $relay->log->type,
            'version' => null,
        ]);

        $event = new NeedsEnvelopes($relay);

        (new Gather)->handle($event);

        $this->assertNull($event->envelopes->first()->version);
    }
}
