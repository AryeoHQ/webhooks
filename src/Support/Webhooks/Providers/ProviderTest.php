<?php

declare(strict_types=1);

namespace Support\Webhooks\Providers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;
use Support\Webhooks\Collecting\Listeners\Gather;
use Support\Webhooks\Sending\Events\NeedsSent;
use Support\Webhooks\Sending\Listeners\Deliver;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_collecting_listener(): void
    {
        Event::fake();
        Event::assertListening(NeedsEnvelopes::class, Gather::class);
    }

    #[Test]
    public function it_registers_the_sending_listener(): void
    {
        Event::fake();
        Event::assertListening(NeedsSent::class, Deliver::class);
    }
}
