<?php

declare(strict_types=1);

namespace Support\Webhooks\Providers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Webhooks\Sending\Events\NeedsSent;
use Support\Webhooks\Sending\Listeners\Deliver;
use Support\Webhooks\Subscriptions\Listeners\AutoDisable;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_sending_listener(): void
    {
        Event::fake();
        Event::assertListening(NeedsSent::class, Deliver::class);
    }

    #[Test]
    public function it_registers_the_disable_on_failure_listener(): void
    {
        Event::fake();
        Event::assertListening(Failed::class, AutoDisable::class);
    }
}
