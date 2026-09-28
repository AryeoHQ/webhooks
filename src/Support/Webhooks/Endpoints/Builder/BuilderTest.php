<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints\Builder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Endpoint;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Builder::class)]
final class BuilderTest extends TestCase
{
    #[Test]
    public function for_scopes_by_event_alias(): void
    {
        $orderPlaced = Transportable::factory()->create(['id' => 'order.placed']);
        $orderCancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $subscriber = Subscriber::factory()->create();

        $placed = Endpoint::factory()->for($subscriber)->hasAttached($orderPlaced, [], 'events')->create();

        Endpoint::factory()->for($subscriber)->hasAttached($orderCancelled, [], 'events')->create();

        $results = Endpoint::for('order.placed')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($placed->is($results->first()));
    }

    #[Test]
    public function for_includes_endpoint_subscribed_to_multiple_events(): void
    {
        $orderPlaced = Transportable::factory()->create(['id' => 'order.placed']);
        $orderCancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $subscriber = Subscriber::factory()->create();

        $endpoint = Endpoint::factory()->for($subscriber)->hasAttached([$orderPlaced, $orderCancelled], [], 'events')->create();

        $this->assertTrue($endpoint->is(Endpoint::for('order.placed')->sole()));
        $this->assertTrue($endpoint->is(Endpoint::for('order.cancelled')->sole()));
    }

    #[Test]
    public function active_excludes_inactive_endpoints(): void
    {
        $subscriber = Subscriber::factory()->create();

        Endpoint::factory()->for($subscriber)->active()->create();
        Endpoint::factory()->for($subscriber)->inactive()->create();

        $results = Endpoint::active()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function inactive_excludes_active_endpoints(): void
    {
        $subscriber = Subscriber::factory()->create();

        Endpoint::factory()->for($subscriber)->active()->create();
        Endpoint::factory()->for($subscriber)->inactive()->create();

        $results = Endpoint::inactive()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function disabled_excludes_active_endpoints(): void
    {
        $subscriber = Subscriber::factory()->create();

        Endpoint::factory()->for($subscriber)->active()->create();
        Endpoint::factory()->for($subscriber)->disabled()->create();

        $results = Endpoint::disabled()->get();

        $this->assertCount(1, $results);
    }
}
