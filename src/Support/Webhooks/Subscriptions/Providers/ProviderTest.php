<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Providers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Status\Events\Failed;
use Support\Webhooks\Subscriptions\Listeners\AutoDisable;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_auto_disable_listener(): void
    {
        Event::fake();
        Event::assertListening(Failed::class, AutoDisable::class);
    }
}
