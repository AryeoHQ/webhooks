<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions;

use Illuminate\Database\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Endpoint;
use Support\Webhooks\Subscriptions\Listeners\Cleanup;
use Support\Webhooks\Subscriptions\Listeners\Detach;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\Fixtures\Support\Webhooks\Endpoints\Endpoint as ExtendedEndpoint;
use Tests\TestCase;

#[CoversClass(Subscription::class)]
#[CoversClass(Cleanup::class)]
#[CoversClass(Detach::class)]
final class SubscriptionTest extends TestCase
{
    #[Test]
    public function it_belongs_to_an_endpoint(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'events')->create();

        $subscription = $endpoint->events->first()->subscription;

        $this->assertTrue($endpoint->is($subscription->endpoint));
    }

    #[Test]
    public function it_belongs_to_an_event(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'events')->create();

        $subscription = $endpoint->events->first()->subscription;

        $this->assertSame('order.placed', $subscription->event->id);
    }

    #[Test]
    public function an_endpoint_cannot_subscribe_to_the_same_event_twice(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'events')->create();

        $this->expectException(UniqueConstraintViolationException::class);

        $endpoint->events()->attach($transportable);
    }

    #[Test]
    public function two_endpoints_can_subscribe_to_the_same_event(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $subscriber = Subscriber::factory()->create();

        Endpoint::factory()->for($subscriber)->hasAttached($transportable, [], 'events')->create();
        Endpoint::factory()->for($subscriber)->hasAttached($transportable, [], 'events')->create();

        $this->assertSame(2, Subscription::query()->where('event_log_transportable_id', 'order.placed')->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function deleting_an_endpoint_deletes_its_subscriptions(): void
    {
        $placed = Transportable::factory()->create(['id' => 'order.placed']);
        $cancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached([$placed, $cancelled], [], 'events')->create();

        $endpoint->delete();

        $this->assertSame(0, Subscription::query()->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function deleting_an_endpoint_leaves_other_endpoints_subscriptions_alone(): void
    {
        $placed = Transportable::factory()->create(['id' => 'order.placed']);
        $cancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $subscriber = Subscriber::factory()->create();

        $deleted = Endpoint::factory()->for($subscriber)->hasAttached($placed, [], 'events')->create();

        $kept = Endpoint::factory()->for($subscriber)->hasAttached($cancelled, [], 'events')->create();

        $deleted->delete();

        $this->assertSame(1, $kept->events()->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function deleting_an_extended_endpoint_deletes_its_subscriptions(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        Endpoint::use(ExtendedEndpoint::class);

        try {
            $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'events')->create();

            $this->assertInstanceOf(ExtendedEndpoint::class, $endpoint);

            $endpoint->delete();

            $this->assertSame(0, Subscription::query()->count()); // @phpstan-ignore staticMethod.dynamicCall
        } finally {
            Endpoint::use(Endpoint::class);
        }
    }

    #[Test]
    public function deleting_a_transportable_detaches_its_subscriptions(): void
    {
        $placed = Transportable::factory()->create(['id' => 'order.placed']);
        $cancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        Endpoint::factory()->for(Subscriber::factory())->hasAttached([$placed, $cancelled], [], 'events')->create();

        $placed->delete();

        $this->assertSame(1, Subscription::query()->count()); // @phpstan-ignore staticMethod.dynamicCall
        $this->assertSame('order.cancelled', Subscription::query()->sole()->event_log_transportable_id); // @phpstan-ignore staticMethod.dynamicCall
    }
}
