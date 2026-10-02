<?php

declare(strict_types=1);

namespace Support\Webhooks\Transportables\Scopes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Contracts\Webhook;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\Fixtures\Support\Webhooks\WebhookEvent;
use Tests\TestCase;

#[CoversClass(Webhookable::class)]
final class WebhookableTest extends TestCase
{
    #[Test]
    public function it_only_includes_transportables_with_the_webhook_transport(): void
    {
        Transportable::factory()->create(['transports' => [Webhook::class]]);
        Transportable::factory()->create(['transports' => [Mqtt::class, Webhook::class]]);
        Transportable::factory()->create(['transports' => [Mqtt::class]]);
        Transportable::factory()->create(['transports' => []]);

        $this->assertCount(2, WebhookEvent::all());
        $this->assertCount(4, Transportable::all());
    }
}
