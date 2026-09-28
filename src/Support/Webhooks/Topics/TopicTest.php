<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics;

use Illuminate\Database\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Endpoint;
use Support\Webhooks\Topics\Listeners\Cleanup;
use Support\Webhooks\Topics\Listeners\Detach;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\Fixtures\Support\Webhooks\Endpoints\Endpoint as ExtendedEndpoint;
use Tests\TestCase;

#[CoversClass(Topic::class)]
#[CoversClass(Cleanup::class)]
#[CoversClass(Detach::class)]
final class TopicTest extends TestCase
{
    #[Test]
    public function it_belongs_to_an_endpoint(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'topics')->create();

        $pivot = $endpoint->topics->first()->pivot;

        $this->assertTrue($endpoint->is($pivot->endpoint));
    }

    #[Test]
    public function it_belongs_to_a_transportable(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'topics')->create();

        $pivot = $endpoint->topics->first()->pivot;

        $this->assertSame('order.placed', $pivot->transportable->id);
    }

    #[Test]
    public function an_endpoint_cannot_subscribe_to_the_same_topic_twice(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'topics')->create();

        $this->expectException(UniqueConstraintViolationException::class);

        $endpoint->topics()->attach($transportable);
    }

    #[Test]
    public function two_endpoints_can_subscribe_to_the_same_event(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $subscriber = Subscriber::factory()->create();

        Endpoint::factory()->for($subscriber)->hasAttached($transportable, [], 'topics')->create();
        Endpoint::factory()->for($subscriber)->hasAttached($transportable, [], 'topics')->create();

        $this->assertSame(2, Topic::query()->where('event_log_transportable_id', 'order.placed')->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function deleting_an_endpoint_deletes_its_topics(): void
    {
        $placed = Transportable::factory()->create(['id' => 'order.placed']);
        $cancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached([$placed, $cancelled], [], 'topics')->create();

        $endpoint->delete();

        $this->assertSame(0, Topic::query()->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function deleting_an_endpoint_leaves_other_endpoints_topics_alone(): void
    {
        $placed = Transportable::factory()->create(['id' => 'order.placed']);
        $cancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        $subscriber = Subscriber::factory()->create();

        $deleted = Endpoint::factory()->for($subscriber)->hasAttached($placed, [], 'topics')->create();

        $kept = Endpoint::factory()->for($subscriber)->hasAttached($cancelled, [], 'topics')->create();

        $deleted->delete();

        $this->assertSame(1, $kept->topics()->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function deleting_an_extended_endpoint_deletes_its_topics(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        Endpoint::use(ExtendedEndpoint::class);

        try {
            $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'topics')->create();

            $this->assertInstanceOf(ExtendedEndpoint::class, $endpoint);

            $endpoint->delete();

            $this->assertSame(0, Topic::query()->count()); // @phpstan-ignore staticMethod.dynamicCall
        } finally {
            Endpoint::use(Endpoint::class);
        }
    }

    #[Test]
    public function deleting_a_transportable_detaches_its_topics(): void
    {
        $placed = Transportable::factory()->create(['id' => 'order.placed']);
        $cancelled = Transportable::factory()->create(['id' => 'order.cancelled']);

        Endpoint::factory()->for(Subscriber::factory())->hasAttached([$placed, $cancelled], [], 'topics')->create();

        $placed->delete();

        $this->assertSame(1, Topic::query()->count()); // @phpstan-ignore staticMethod.dynamicCall
        $this->assertSame('order.cancelled', Topic::query()->sole()->event_log_transportable_id); // @phpstan-ignore staticMethod.dynamicCall
    }
}
