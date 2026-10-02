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
use Tests\Fixtures\Support\Events\Log\Transportables\SwappedTransportable;
use Tests\TestCase;

#[CoversClass(Detach::class)]
final class DetachTest extends TestCase
{
    #[Test]
    public function it_deletes_subscriptions_when_deleting_a_transportable(): void
    {
        $deleted = Transportable::factory()->create();
        $kept = Transportable::factory()->create();

        Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached([$deleted, $kept], [], 'events')->create();

        $deleted->delete();

        $this->assertSame($kept->getKey(), Subscription::query()->sole()->event_log_transportable_id); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[DefineEnvironment('swapTransportable')]
    #[Test]
    public function it_deletes_subscriptions_when_deleting_a_swapped_transportable(): void
    {
        $deleted = Transportable::factory()->create();
        $kept = Transportable::factory()->create();

        $this->assertInstanceOf(SwappedTransportable::class, $deleted);

        Endpoint::factory()->for(Subscriber::factory(), 'principal')->hasAttached([$deleted, $kept], [], 'events')->create();

        $deleted->delete();

        $this->assertSame($kept->getKey(), Subscription::query()->sole()->event_log_transportable_id); // @phpstan-ignore staticMethod.dynamicCall
    }

    protected function swapTransportable(): void // @phpstan-ignore ergebnis.privateInFinalClass
    {
        Transportable::use(SwappedTransportable::class);
    }
}
