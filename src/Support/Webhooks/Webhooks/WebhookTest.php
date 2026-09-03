<?php

declare(strict_types=1);

namespace Support\Webhooks\Webhooks;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Envelopes\Envelope;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Webhook::class)]
final class WebhookTest extends TestCase
{
    #[Test]
    public function it_exposes_delivery_properties(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly();

        $webhook = Webhook::make($delivery);

        $this->assertSame($delivery->id, $webhook->id);
        $this->assertSame($delivery->relay->log->type, $webhook->type);
        $this->assertSame($delivery->payload, $webhook->data);
        $this->assertTrue($delivery->relay->log->occurred_at->equalTo($webhook->time));
    }

    #[Test]
    public function it_resolves_source_from_config(): void
    {
        config(['app.url' => $url = 'https://example.com']);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $webhook = Webhook::make($delivery);

        $this->assertSame($url, $webhook->source);
    }

    #[Test]
    public function it_serializes_to_a_cloud_event_json_string(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly();

        $json = Webhook::make($delivery)->payload;

        $decoded = json_decode($json, true);

        $this->assertSame($delivery->id, $decoded['id']);
        $this->assertSame($delivery->relay->log->type, $decoded['type']);
        $this->assertSame('1.0', $decoded['specversion']);
        $this->assertSame('application/json', $decoded['datacontenttype']);
        $this->assertArrayHasKey('source', $decoded);
        $this->assertArrayHasKey('time', $decoded);
    }

    #[Test]
    public function it_includes_idempotency_key_and_signature_headers(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly();
        /** @var Subscription $subscription */
        $subscription = $delivery->recipient;

        $webhook = Webhook::make($delivery);
        $headers = $webhook->headers;

        $this->assertSame($delivery->id, $headers['Idempotency-Key']);
        $this->assertSame(
            hash_hmac('sha256', $webhook->payload, $subscription->secret),
            $headers['X-Webhook-Signature'],
        );
    }

    #[Test]
    public function it_includes_custom_subscription_headers(): void
    {
        $subscription = Subscription::factory()->for(Subscriber::factory())->create([
            'headers' => ['X-Custom' => 'value'],
        ]);

        $delivery = Delivery::factory()->webhook()->createQuietly([
            'envelope' => Envelope::make(recipient: $subscription),
        ]);

        $headers = Webhook::make($delivery)->headers;

        $this->assertSame('value', $headers['X-Custom']);
    }

    #[Test]
    public function it_delivers_to_the_subscription_url(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();
        /** @var Subscription $subscription */
        $subscription = $delivery->recipient;

        Webhook::make($delivery)->deliver();

        Http::assertSent(fn (Request $request): bool => $request->url() === $subscription->url);
    }

    #[Test]
    public function it_sends_cloud_event_content_type(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        Webhook::make($delivery)->deliver();

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Content-Type', 'application/cloudevents+json'));
    }
}
