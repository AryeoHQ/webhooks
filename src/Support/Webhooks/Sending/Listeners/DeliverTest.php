<?php

declare(strict_types=1);

namespace Support\Webhooks\Sending\Listeners;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Webhooks\Sending\Events\NeedsSent;
use Support\Webhooks\Subscriptions\Subscription;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;
use Tests\TestCase;

#[CoversClass(Deliver::class)]
final class DeliverTest extends TestCase
{
    #[Test]
    public function it_posts_to_the_subscription_url(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        /** @var Subscription $subscription */
        $subscription = $delivery->recipient;

        $event = new NeedsSent($delivery);

        (new Deliver)->handle($event);

        Http::assertSent(fn (Request $request): bool => $request->url() === $subscription->url);
    }

    #[Test]
    public function it_sends_the_idempotency_key_header(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        (new Deliver)->handle($event);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', $delivery->id));
    }

    #[Test]
    public function it_sends_an_hmac_signature_header(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        /** @var Subscription $subscription */
        $subscription = $delivery->recipient;

        $event = new NeedsSent($delivery);

        (new Deliver)->handle($event);

        Http::assertSent(function (Request $request) use ($subscription): bool {
            $timestamp = $request->header('Timestamp')[0];
            $expected = hash_hmac('sha256', "{$timestamp}.{$request->body()}", $subscription->secret);

            return $request->header('Signature')[0] === $expected;
        });
    }

    #[Test]
    public function it_includes_custom_headers_from_the_subscription(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $subscription = Subscription::factory()->for(Subscriber::factory())->create([
            'headers' => ['X-Custom' => 'value'],
        ]);

        $delivery = Delivery::factory()->webhook()->createQuietly([
            'envelope' => \Support\Events\Log\Envelopes\Envelope::make(recipient: $subscription),
        ]);

        $event = new NeedsSent($delivery);

        (new Deliver)->handle($event);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-Custom', 'value'));
    }

    #[Test]
    public function it_records_the_response_body(): void
    {
        Http::fake(['*' => Http::response('recorded')]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        (new Deliver)->handle($event);

        $this->assertSame('recorded', $event->result->message);
        $this->assertSame(200, $event->result->code);
    }

    #[Test]
    public function it_throws_on_http_failure(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        (new Deliver)->handle($event);
    }

    #[Test]
    public function it_records_the_response_body_even_on_failure(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        try {
            (new Deliver)->handle($event);
        } catch (\Illuminate\Http\Client\RequestException) {
            // expected
        }

        $this->assertSame('error', $event->result->message);
        $this->assertSame(500, $event->result->code);
    }

    #[Test]
    public function it_throws_undeliverable_on_410(): void
    {
        Http::fake(['*' => Http::response('gone', 410)]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        $this->expectException(\Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable::class);

        (new Deliver)->handle($event);
    }

    #[Test]
    public function it_throws_undeliverable_on_421(): void
    {
        Http::fake(['*' => Http::response('misdirected', 421)]);

        $delivery = Delivery::factory()->webhook()->createQuietly();

        $event = new NeedsSent($delivery);

        $this->expectException(\Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable::class);

        (new Deliver)->handle($event);
    }
}
