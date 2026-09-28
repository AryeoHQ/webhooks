<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\Fixtures\Support\Webhooks\Endpoints\Endpoint as ExtendedEndpoint;
use Tests\TestCase;

#[CoversClass(Endpoint::class)]
#[CoversTrait(GeneratesSecret::class)]
final class EndpointTest extends TestCase
{
    #[Test]
    public function it_auto_generates_a_secret_on_creation(): void
    {
        $endpoint = Endpoint::factory()->for(Subscriber::factory())->create();

        $this->assertNotNull($endpoint->secret);
        $this->assertSame(64, strlen($endpoint->secret));
    }

    #[Test]
    public function it_preserves_an_explicitly_set_secret(): void
    {
        $endpoint = Endpoint::factory()->for(Subscriber::factory())->create(['secret' => 'explicit']);

        $this->assertSame('explicit', $endpoint->secret);
    }

    #[Test]
    public function it_casts_headers_to_array(): void
    {
        $endpoint = Endpoint::factory()->for(Subscriber::factory())->create([
            'headers' => ['X-Custom' => 'value'],
        ]);

        $endpoint->refresh();

        $this->assertSame(['X-Custom' => 'value'], $endpoint->headers);
    }

    #[Test]
    public function it_belongs_to_a_subscriber(): void
    {
        $subscriber = Subscriber::factory()->create();

        $endpoint = Endpoint::factory()->for($subscriber)->create();

        $this->assertTrue($subscriber->is($endpoint->subscriber));
    }

    #[Test]
    public function it_uses_itself_by_default(): void
    {
        $this->assertSame(Endpoint::class, Endpoint::using());
    }

    #[Test]
    public function it_uses_the_model_given_to_use(): void
    {
        Endpoint::use(ExtendedEndpoint::class);

        try {
            $this->assertSame(ExtendedEndpoint::class, Endpoint::using());
            $this->assertInstanceOf(ExtendedEndpoint::class, Endpoint::factory()->make());
        } finally {
            Endpoint::use(Endpoint::class);
        }
    }

    #[Test]
    public function it_has_events(): void
    {
        $transportable = Transportable::factory()->create(['id' => 'order.placed']);

        $endpoint = Endpoint::factory()->for(Subscriber::factory())->hasAttached($transportable, [], 'events')->create();

        $this->assertCount(1, $endpoint->events);
        $this->assertSame('order.placed', $endpoint->events->first()->id);
    }

    #[Test]
    public function it_sets_subscriber_via_fill(): void
    {
        $subscriber = Subscriber::factory()->create();

        $endpoint = new Endpoint;
        $endpoint->fill(['subscriber' => $subscriber]);

        $this->assertSame($subscriber->getMorphClass(), $endpoint->subscriber_type);
        $this->assertSame($subscriber->getKey(), $endpoint->subscriber_id);
    }

    #[Test]
    public function it_hides_the_secret(): void
    {
        $endpoint = Endpoint::factory()->for(Subscriber::factory())->create();

        $this->assertArrayNotHasKey('secret', $endpoint->toArray());
    }

    #[Test]
    public function it_defaults_to_active_status(): void
    {
        $endpoint = Endpoint::factory()->for(Subscriber::factory())->create();

        $this->assertSame(Status\Status::Active, $endpoint->status->enum);
    }
}
