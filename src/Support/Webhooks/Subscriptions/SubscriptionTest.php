<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Subscription::class)]
final class SubscriptionTest extends TestCase
{
    #[Test]
    public function it_auto_generates_a_secret_on_creation(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create();

        $this->assertNotNull($subscription->secret);
        $this->assertSame(64, strlen($subscription->secret));
    }

    #[Test]
    public function it_preserves_an_explicitly_set_secret(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create(['secret' => 'explicit']);

        $this->assertSame('explicit', $subscription->secret);
    }

    #[Test]
    public function it_casts_headers_to_array(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create([
            'headers' => ['X-Custom' => 'value'],
        ]);

        $subscription->refresh();

        $this->assertSame(['X-Custom' => 'value'], $subscription->headers);
    }

    #[Test]
    public function it_belongs_to_a_subscriber(): void
    {
        $subscriber = Subscriber::factory()->create();

        $subscription = Subscription::factory()->for($subscriber)->create();

        $this->assertTrue($subscriber->is($subscription->subscriber));
    }
}
