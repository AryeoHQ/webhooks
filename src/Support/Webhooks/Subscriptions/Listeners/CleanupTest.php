<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Listeners;

use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Endpoint;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\Fixtures\Support\Webhooks\Endpoints\SwappedEndpoint;
use Tests\TestCase;

#[CoversClass(Cleanup::class)]
final class CleanupTest extends TestCase
{
    #[Test]
    public function it_deletes_subscriptions_when_deleting_an_endpoint(): void
    {
        $transportable = Transportable::factory()->create();

        $deleted = Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached($transportable, [], 'events')->create();
        $kept = Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached($transportable, [], 'events')->create();

        $deleted->delete();

        $this->assertSame($kept->getKey(), Subscription::query()->sole()->webhook_endpoint_id); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[DefineEnvironment('swapEndpoint')]
    #[Test]
    public function it_deletes_subscriptions_when_deleting_a_swapped_endpoint(): void
    {
        $transportable = Transportable::factory()->create();

        $deleted = Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached($transportable, [], 'events')->create();
        $kept = Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached($transportable, [], 'events')->create();

        $this->assertInstanceOf(SwappedEndpoint::class, $deleted);

        $deleted->delete();

        $this->assertSame($kept->getKey(), Subscription::query()->sole()->webhook_endpoint_id); // @phpstan-ignore staticMethod.dynamicCall
    }

    protected function swapEndpoint(): void // @phpstan-ignore ergebnis.privateInFinalClass
    {
        Endpoint::use(SwappedEndpoint::class);
    }
}
