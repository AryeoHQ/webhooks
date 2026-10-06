<?php

declare(strict_types=1);

namespace Support\Webhooks\Webhooks;

use DateTime;
use DateTimeInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Envelopes\Envelope;
use Support\Webhooks\Endpoints\Endpoint;
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
        $this->assertTrue($delivery->relay->log->occurred_at->equalTo($webhook->occurredAt));
    }

    #[Test]
    public function it_resolves_source_from_config(): void
    {
        config(['webhooks.source' => $url = 'https://webhooks.example.com']);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $webhook = Webhook::make($delivery);

        $this->assertSame($url, $webhook->source);
    }

    #[Test]
    public function it_falls_back_to_the_app_url_when_source_is_blank(): void
    {
        config(['app.url' => $url = 'https://example.com']);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        config(['webhooks.source' => null]);
        $this->assertSame($url, Webhook::make($delivery)->source);

        config(['webhooks.source' => '']);
        $this->assertSame($url, Webhook::make($delivery)->source);

        config(['webhooks.source' => ' ']);
        $this->assertSame($url, Webhook::make($delivery)->source);
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
    public function it_uses_the_custom_cloud_event_normalizer(): void
    {
        Date::serializeUsing(fn (DateTimeInterface $date): string => $date->format(DateTime::RFC3339_EXTENDED));

        try {
            $webhook = Webhook::make(Delivery::factory()->webhook()->createQuietly());

            $decoded = json_decode($webhook->payload, true);

            $this->assertSame($webhook->occurredAt->jsonSerialize(), $decoded['time']);
        } finally {
            Date::serializeUsing(null);
        }
    }

    #[Test]
    public function it_includes_idempotency_key_and_signature_headers(): void
    {
        $delivery = Delivery::factory()->webhook()->createQuietly();
        /** @var Endpoint $endpoint */
        $endpoint = $delivery->recipient;

        $webhook = Webhook::make($delivery);
        $headers = $webhook->headers;

        $this->assertSame($delivery->id, $headers['Idempotency-Key']);
        $this->assertSame($webhook->source, $headers['Source']);
        $this->assertSame($webhook->timestamp, $headers['Timestamp']);

        $expected = hash_hmac('sha256', "{$webhook->timestamp}.{$webhook->payload}", $endpoint->secret);

        $this->assertSame($expected, $headers['Signature']);
    }

    #[Test]
    public function it_includes_custom_endpoint_headers(): void
    {
        $endpoint = Endpoint::factory()->for(Subscriber::factory(), 'principal')->create([
            'headers' => ['X-Custom' => 'value'],
        ]);

        $delivery = Delivery::factory()->webhook()->createQuietly([
            'envelope' => Envelope::make(recipient: $endpoint),
        ]);

        $headers = Webhook::make($delivery)->headers;

        $this->assertSame('value', $headers['X-Custom']);
    }

    #[Test]
    public function it_delivers_to_the_endpoint_url(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();
        /** @var Endpoint $endpoint */
        $endpoint = $delivery->recipient;

        Webhook::make($delivery)->deliver();

        Http::assertSent(fn (Request $request): bool => $request->url() === $endpoint->url);
    }

    #[Test]
    public function it_sends_cloud_event_content_type(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        Webhook::make($delivery)->deliver();

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Content-Type', 'application/cloudevents+json'));
    }

    #[Test]
    public function it_applies_the_configured_timeouts(): void
    {
        config(['webhooks.timeouts.connect' => 3, 'webhooks.timeouts.request' => 7]);

        $options = [];

        Http::fake(function (Request $request, array $sent) use (&$options) {
            $options = $sent;

            return Http::response('ok');
        });

        Webhook::make(Delivery::factory()->webhook()->createQuietly())->deliver();

        $this->assertSame(3, $options['connect_timeout']);
        $this->assertSame(7, $options['timeout']);
    }
}
