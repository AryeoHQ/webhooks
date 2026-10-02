<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions;

use Illuminate\Database\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Endpoint;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Subscription::class)]
final class SubscriptionTest extends TestCase
{
    #[Test]
    public function it_belongs_to_an_endpoint(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached($transportable, [], 'events')->create();

        $subscription = $endpoint->events->first()->subscription;

        $this->assertTrue($endpoint->is($subscription->endpoint));
    }

    #[Test]
    public function it_belongs_to_an_event(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached($transportable, [], 'events')->create();

        $subscription = $endpoint->events->first()->subscription;

        $this->assertSame('order.placed', $subscription->event->id);
    }

    #[Test]
    public function an_endpoint_cannot_subscribe_to_the_same_event_twice(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached($transportable, [], 'events')->create();

        $this->expectException(UniqueConstraintViolationException::class);

        $endpoint->events()->attach($transportable);
    }

    #[Test]
    public function two_endpoints_can_subscribe_to_the_same_event(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $subscriber = Subscriber::factory()->create();

        Endpoint::factory()->for($subscriber, 'principal')->hasAttached($transportable, [], 'events')->create();
        Endpoint::factory()->for($subscriber, 'principal')->hasAttached($transportable, [], 'events')->create();

        $this->assertSame(2, Subscription::query()->where('event_log_transportable_id', 'order.placed')->count()); // @phpstan-ignore staticMethod.dynamicCall
    }
}
