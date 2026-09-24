<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\Fixtures\Support\Webhooks\Subscriptions\Subscription as ExtendedSubscription;
use Tests\TestCase;

#[CoversClass(Subscription::class)]
#[CoversTrait(GeneratesSecret::class)]
final class SubscriptionTest extends TestCase
{
    #[Test]
    public function it_auto_generates_a_secret_on_creation(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create();

        $this->assertNotNull($subscription->secret);
        $this->assertSame(64, strlen($subscription->secret));
    }

    #[Test]
    public function it_preserves_an_explicitly_set_secret(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create(['secret' => 'explicit']);

        $this->assertSame('explicit', $subscription->secret);
    }

    #[Test]
    public function it_casts_headers_to_array(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create([
            'headers' => ['X-Custom' => 'value'],
        ]);

        $subscription->refresh();

        $this->assertSame(['X-Custom' => 'value'], $subscription->headers);
    }

    #[Test]
    public function it_belongs_to_a_subscriber(): void
    {
        $subscriber = Subscriber::factory()->create();

        $subscription = Subscription::factory()->for($subscriber)->create();

        $this->assertTrue($subscriber->is($subscription->subscriber));
    }

    #[Test]
    public function it_uses_itself_by_default(): void
    {
        $this->assertSame(Subscription::class, Subscription::using());
    }

    #[Test]
    public function it_uses_the_model_given_to_use(): void
    {
        Subscription::use(ExtendedSubscription::class);

        try {
            $this->assertSame(ExtendedSubscription::class, Subscription::using());
            $this->assertInstanceOf(ExtendedSubscription::class, Subscription::factory()->make());
        } finally {
            Subscription::use(Subscription::class);
        }
    }

    #[Test]
    public function it_has_topics(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $subscription = Subscription::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'topics')->create();

        $this->assertCount(1, $subscription->topics);
        $this->assertSame('order.placed', $subscription->topics->first()->id);
    }

    #[Test]
    public function it_sets_subscriber_via_fill(): void
    {
        $subscriber = Subscriber::factory()->create();

        $subscription = new Subscription;
        $subscription->fill(['subscriber' => $subscriber]);

        $this->assertSame($subscriber->getMorphClass(), $subscription->subscriber_type);
        $this->assertSame($subscriber->getKey(), $subscription->subscriber_id);
    }

    #[Test]
    public function it_defaults_to_active_status(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create();

        $this->assertSame(Status\Status::Active, $subscription->status->enum);
    }
}
