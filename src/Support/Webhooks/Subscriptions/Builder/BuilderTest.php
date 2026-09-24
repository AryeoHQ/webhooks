<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Builder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Builder::class)]
final class BuilderTest extends TestCase
{
    #[Test]
    public function for_scopes_by_topic_alias(): void
    {
        $orderPlaced = Transportable::factory()->create(['id' => 'order.placed']);
        $orderCancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $subscriber = Subscriber::factory()->create();

        $placed = Subscription::factory()->for($subscriber)->hasAttached($orderPlaced, [], 'topics')->create();

        Subscription::factory()->for($subscriber)->hasAttached($orderCancelled, [], 'topics')->create();

        $results = Subscription::for('order.placed')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($placed->is($results->first()));
    }

    #[Test]
    public function for_includes_subscription_subscribed_to_multiple_topics(): void
    {
        $orderPlaced = Transportable::factory()->create(['id' => 'order.placed']);
        $orderCancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $subscriber = Subscriber::factory()->create();

        $subscription = Subscription::factory()->for($subscriber)->hasAttached([$orderPlaced, $orderCancelled], [], 'topics')->create();

        $this->assertTrue($subscription->is(Subscription::for('order.placed')->sole()));
        $this->assertTrue($subscription->is(Subscription::for('order.cancelled')->sole()));
    }

    #[Test]
    public function active_excludes_inactive_subscriptions(): void
    {
        $subscriber = Subscriber::factory()->create();

        Subscription::factory()->for($subscriber)->active()->create();
        Subscription::factory()->for($subscriber)->inactive()->create();

        $results = Subscription::active()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function inactive_excludes_active_subscriptions(): void
    {
        $subscriber = Subscriber::factory()->create();

        Subscription::factory()->for($subscriber)->active()->create();
        Subscription::factory()->for($subscriber)->inactive()->create();

        $results = Subscription::inactive()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function disabled_excludes_active_subscriptions(): void
    {
        $subscriber = Subscriber::factory()->create();

        Subscription::factory()->for($subscriber)->active()->create();
        Subscription::factory()->for($subscriber)->disabled()->create();

        $results = Subscription::disabled()->get();

        $this->assertCount(1, $results);
    }
}
