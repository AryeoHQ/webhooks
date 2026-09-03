<?php

declare(strict_types=1);

namespace Support\Webhooks\Webhooks;

use Carbon\CarbonImmutable;
use CloudEvents\Serializers\JsonSerializer;
use CloudEvents\V1\CloudEventImmutable;
use Illuminate\Support\Facades\Http;
use Support\Events\Log\Deliveries\Delivery;
use Support\Webhooks\Subscriptions\Subscription;

class Webhook
{
    public private(set) string $id {
        get => $this->id ??= $this->delivery->id;
    }

    public private(set) string $source {
        get => $this->source ??= (string) config('app.url');
    }

    public private(set) string $type {
        get => $this->type ??= $this->delivery->relay->log->type;
    }

    /** @var array<string, mixed>|null */
    public private(set) array|null $data {
        get => $this->data ??= $this->delivery->payload;
    }

    public private(set) CarbonImmutable $time {
        get => $this->time ??= $this->delivery->relay->log->occurred_at;
    }

    public private(set) string $payload {
        get => $this->payload ??= JsonSerializer::create()->serializeStructured($this->cloudEvent);
    }

    /** @var array<string, string> */
    public private(set) array $headers {
        get => $this->headers ??= [
            'Idempotency-Key' => $this->id,
            'X-Webhook-Signature' => hash_hmac('sha256', $this->payload, $this->subscription->secret),
            ...$this->subscription->headers ?? [],
        ];
    }

    private Subscription $subscription {
        get => $this->subscription ??= $this->delivery->recipient; // @phpstan-ignore assign.propertyType, return.type
    }

    private CloudEventImmutable $cloudEvent {
        get => $this->cloudEvent ??= new CloudEventImmutable(
            id: $this->id,
            source: $this->source,
            type: $this->type,
            data: $this->data,
            dataContentType: 'application/json',
            time: $this->time,
        );
    }

    private readonly Delivery $delivery;

    private function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public static function make(Delivery $delivery): self
    {
        return new self($delivery);
    }

    public function deliver(): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders($this->headers)
            ->withBody($this->payload, 'application/cloudevents+json')
            ->post($this->subscription->url);
    }
}
