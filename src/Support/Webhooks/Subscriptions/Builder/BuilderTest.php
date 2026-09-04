<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Builder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Builder::class)]
final class BuilderTest extends TestCase
{
    #[Test]
    public function where_event_scopes_by_alias(): void
    {
        $subscriber = Subscriber::factory()->create();

        Subscription::factory()->for($subscriber)->create(['event' => 'order.placed']);
        Subscription::factory()->for($subscriber)->create(['event' => 'order.cancelled']);

        $results = Subscription::for('order.placed')->get();

        $this->assertCount(1, $results);
        $this->assertSame('order.placed', $results->first()->event);
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
