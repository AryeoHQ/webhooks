<?php

declare(strict_types=1);

namespace Support\Webhooks\Contracts;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Support\Events\Log\Transports\Dispatches\Queues;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;
use Support\Webhooks\Sending\Events\NeedsSent;
use Tests\TestCase;

#[CoversNothing]
final class WebhookTest extends TestCase
{
    #[Test]
    public function it_declares_dispatches(): void
    {
        $dispatches = Dispatches::on(Webhook::class);

        $this->assertSame(NeedsEnvelopes::class, $dispatches->collecting);
        $this->assertSame(NeedsSent::class, $dispatches->sending);
    }

    #[Test]
    public function it_declares_queue_config_keys(): void
    {
        $queues = Queues::on(Webhook::class);

        $this->assertSame('webhooks.queues.collecting', $queues->collecting);
        $this->assertSame('webhooks.queues.sending', $queues->sending);
    }

    #[Test]
    public function it_is_discovered_as_a_transport(): void
    {
        $interfaces = (new ReflectionClass(Webhook::class))->getInterfaceNames();

        $this->assertContains(\Support\Events\Log\Transports\Contracts\Transport::class, $interfaces);
    }
}
