<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Providers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Events\Deleting as TransportableDeleting;
use Support\Webhooks\Subscriptions\Events\Deleting as SubscriptionDeleting;
use Support\Webhooks\Topics\Listeners\Cleanup;
use Support\Webhooks\Topics\Listeners\Detach;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_cleanup_listener(): void
    {
        Event::fake();
        Event::assertListening(SubscriptionDeleting::class, Cleanup::class);
    }

    #[Test]
    public function it_registers_the_detach_listener(): void
    {
        Event::fake();
        Event::assertListening(TransportableDeleting::class, Detach::class);
    }
}
