<!-- TODO: WebhookSubscriptions need a state machine `Active`, `Inactive`, `Disabled` (`Disabled` is for undeliverable webhook situations) -->

# Webhooks Package — Implementation Plan

## Overview

`aryeo/webhooks` is a standalone consuming relay package for webhook delivery. It depends on `aryeo/event-log` for the relay subsystem and provides everything needed to discover webhook subscriptions, gather envelopes, and send HTTP deliveries.

**Dependency:** `event-log` ← `webhooks`

The package is fully functional on its own. A subscription can pick which payload version it wants; webhooks stores that choice and passes it through to event-log. It does not decide how the value is stored or how it maps to an event's `Version` enum — the consuming app owns that (see "Version is the consumer's, we just carry it" below).

---

## What This Package Provides

| Concern | Artifact |
|---|---|
| Relay discovery | `WebhookRelay` marker interface + `#[Dispatches]` attribute |
| Gathering event | `NeedsWebhookEnvelopes` (implements `NeedsEnvelopes`) |
| Sending event | `NeedsWebhookSent` (implements `NeedsSent`) |
| Subscription model | `WebhookSubscription` (Eloquent) |
| Gathering listener | `GatherWebhookEnvelopes` |
| Sending listener | `SendWebhookDelivery` |
| Service provider | Registers listeners, boots migrations |

---

## Relay Interface

```php
namespace Support\Webhooks\Contracts;

use Support\Events\Log\Transports\Dispatches\Dispatches;
use Support\Events\Log\Transports\Dispatches\Queues;
use Support\Events\Log\Transports\Contracts\Transport;

#[Dispatches(
    collecting: NeedsWebhookEnvelopes::class,
    sending: NeedsWebhookSent::class,
)]
#[Queues(
    collecting: 'webhooks.queues.collecting',
    sending: 'webhooks.queues.sending',
)]
interface WebhookRelay extends Transport {}
```

Bare marker — no method contract. Events implement this interface (alongside `Recordable`) to signal they participate in webhook relay. Discovery happens via `HasRelays` on the event class.

The `#[Queues]` attribute holds config keys (not queue names). The webhook package ships its own config with env-backed defaults (e.g. `'collecting' => env('WEBHOOKS_COLLECTING_QUEUE')`, `'sending' => env('WEBHOOKS_SENDING_QUEUE')`). The consumer sets the env. If the key is unset, event-log falls back to the layer's class-keyed config entry, then the framework default.

> API note (verified against event-log source): the contract is `Support\Events\Log\Transports\Contracts\Transport` (there is no `Relayable`), the attribute is `Support\Events\Log\Transports\Dispatches\Dispatches` with a `collecting:` parameter (not `gathering:`).

---

## Events

### `NeedsWebhookEnvelopes` (gathering)

```php
namespace Support\Webhooks\Events;

use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;
use Support\Events\Log\Transports\Dispatches\Collecting\Provides\CollectsEnvelopes;

class NeedsWebhookEnvelopes implements NeedsEnvelopes
{
    use CollectsEnvelopes;

    public readonly Relay $relay;

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }
}
```

### `NeedsWebhookSent` (sending)

```php
namespace Support\Webhooks\Events;

use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;
use Support\Events\Log\Transports\Dispatches\Sending\Provides\RecordsResult;

class NeedsWebhookSent implements NeedsSent
{
    use RecordsResult;

    public readonly Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }
}
```

> API note: `NeedsSent` implementers must use the `RecordsResult` trait (event-log enforces this at build time). `RecordsResult` provides `result(...)` and exposes `$event->idempotencyKey` (the `Delivery` id) — see the sending listener below.

---

## WebhookSubscription Model

```
Namespace: Support\Webhooks
Table:     webhook_subscriptions
```

### Schema

```php
$table->uuid('id')->primary();
$table->uuidMorphs('subscribable');       // what entity owns this subscription
$table->string('event')->index();         // event alias (e.g., 'order.placed')
$table->string('url');                    // delivery endpoint
$table->string('version')->nullable();    // which payload version to send; opaque to webhooks
$table->json('headers')->nullable();      // custom headers to include with delivery
$table->string('secret');                  // signing secret, auto-generated at creation
$table->boolean('active')->default(true);
$table->timestampsTz();
```

### Key Fields

- `event` — matches the `#[Alias]` value on event classes. Used by `forEvent()` to find matching subscriptions.
- `version` — which payload version this subscription wants. Nullable: null means "send the full `event_logs.data`." Webhooks stores this value as-is and never interprets it. The consuming app decides what goes in and casts it to its event's `Version` enum (see the model note below).
- `subscribable` — polymorphic owner (e.g., Organization, Team, Integration). Scopes subscriptions to a business entity.
- `url` — queried at send time (live reference). If the URL changes between delivery creation and send, the current value is used.
- `secret` — auto-generated at creation time. Used by the sending listener to compute an HMAC signature header. The consuming app decides how to expose this to its consumers (e.g. return it once in an API response).

### Scopes

```php
// Find active subscriptions matching this log's event alias
public static function forEvent(Log $log): Builder
{
    return static::query()
        ->where('event', $log->type)
        ->where('active', true);
}
```

### Model Definition

```php
namespace Support\Webhooks;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    use HasUuids;

    protected $table = 'webhook_subscriptions';

    protected $casts = [
        'headers' => 'array',
        'active' => 'boolean',
    ];

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(fn (self $sub) => $sub->secret ??= Str::random(64));
    }

    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

> **`version` and the consuming app.** `Envelope::make()` takes a `Support\Events\Log\Logs\Data\Version\Contracts\Version` (a marker over `BackedEnum`), and every event defines its own version enum (e.g. `PayloadVersion::V2`). Webhooks cannot know that enum, so it stores `version` as a plain column and leaves the mapping to the consumer. The consuming app casts `version` to its enum — typically by adding `'version' => PayloadVersion::class` to the model's `$casts` (via its own subclass or a configured model). After that cast, `$subscription->version` is a real `Version` and drops straight into `Envelope::make()`. If a consumer never sets a version, it stays null and the full payload is sent.

---

## Listeners

### `GatherWebhookEnvelopes`

Handles the gathering event. Queries subscriptions for the logged event and creates one Envelope per subscription, carrying that subscription's chosen version.

```php
namespace Support\Webhooks\Listeners;

use Support\Events\Log\Envelopes\Envelope;
use Support\Webhooks\Events\NeedsWebhookEnvelopes;
use Support\Webhooks\WebhookSubscription;

class GatherWebhookEnvelopes
{
    public function handle(NeedsWebhookEnvelopes $event): void
    {
        WebhookSubscription::forEvent($event->relay->log)
            ->each(fn (WebhookSubscription $sub) => $event->add(
                Envelope::make(recipient: $sub, version: $sub->version),
            ));
    }
}
```

`$sub->version` is whatever the consuming app cast it to — a `Version` enum or null (see the model note above). Webhooks passes it straight through; event-log reduces it to its backing value and resolves `payload = data_get($log->data, $version)` at delivery time.

**Behavior:**
- Subscription with `version = PayloadVersion::V2` → `Delivery` with `payload = data['v2']`.
- Subscription with `version = null` → `Delivery` with `payload = data` (full).
- Two subscriptions for the same event with different versions → two `Delivery` rows with different payloads.

### `SendWebhookDelivery`

Handles the sending event. Performs the actual HTTP POST to the subscription's URL.

```php
namespace Support\Webhooks\Listeners;

use Illuminate\Support\Facades\Http;
use Support\Webhooks\Events\NeedsWebhookSent;

class SendWebhookDelivery
{
    public function handle(NeedsWebhookSent $event): void
    {
        $subscription = $event->delivery->recipient;
        $body = $this->body($event);

        $response = Http::withHeaders($this->headers($event, $subscription, $body))
            ->post($subscription->url, $body);

        $event->result((string) $response->body());

        $response->throw();
    }

    private function body(NeedsWebhookSent $event): array
    {
        return [
            'id' => $event->idempotencyKey,
            'event' => $event->delivery->relay->log->type,
            'occurred_at' => (string) $event->delivery->relay->log->occurred_at,
            'data' => $event->delivery->payload,
        ];
    }

    private function headers(NeedsWebhookSent $event, WebhookSubscription $subscription, array $body): array
    {
        return [
            'Idempotency-Key' => $event->idempotencyKey,
            'X-Webhook-Signature' => hash_hmac('sha256', json_encode($body), $subscription->secret),
            ...$subscription->headers ?? [],
        ];
    }
}
```

**Behavior:**
- Queries recipient live (current URL/headers/secret, not stale)
- Sends `$event->idempotencyKey` (the `Delivery` id, stable across every attempt and retry) as an `Idempotency-Key` header. This is the last-hop duplicate guard: event-log's internal dedupe stops crash-and-rerun duplicates, but the network send is the one hop a database cannot make idempotent. A receiver that honors the header drops repeats. Sending the header is this package's job — event-log only exposes the key.
- Computes HMAC signature if secret is configured
- Records the HTTP response body via `$event->result()` (event-log stores this on the `DeliveryAttempt.response` column)
- Throws on HTTP failure → the exception propagates through `DeliveryAttempt` Process `failed()` (which records the response and drives the attempt to `Failed`), then up to `Delivery` Process. The Delivery Process trigger has dynamic `$tries` and `$backoff` (seeded from `#[Tries]`), so the queue framework retries the job. On the terminal attempt, `Delivery` Process `failed()` drives the delivery to `Failed`

---

## Service Provider

```php
namespace Support\Webhooks\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Webhooks\Events\NeedsWebhookEnvelopes;
use Support\Webhooks\Events\NeedsWebhookSent;
use Support\Webhooks\Listeners\GatherWebhookEnvelopes;
use Support\Webhooks\Listeners\SendWebhookDelivery;

class Provider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootListeners();
        $this->bootMigrations();
    }

    private function bootListeners(): void
    {
        Event::listen(NeedsWebhookEnvelopes::class, GatherWebhookEnvelopes::class);
        Event::listen(NeedsWebhookSent::class, SendWebhookDelivery::class);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
```

---

## Directory Structure

```
src/Support/Webhooks/
├── Contracts/
│   └── WebhookRelay.php                 # Bare marker + #[Dispatches] + #[Queues]
├── Events/
│   ├── NeedsWebhookEnvelopes.php        # Gathering event
│   └── NeedsWebhookSent.php             # Sending event
├── Listeners/
│   ├── GatherWebhookEnvelopes.php       # Queries subscriptions, builds Envelopes
│   └── SendWebhookDelivery.php          # HTTP POST + HMAC signing
├── Migrations/
│   └── 2026_06_26_000001_create_webhook_subscriptions_table.php
├── Providers/
│   └── Provider.php
└── WebhookSubscription.php              # Eloquent model
```

---

## How Events Opt In

An event class in any package implements the marker and uses `HasRelays`:

```php
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Provides\HasLoggable;
use Support\Events\Log\Provides\HasRelays;
use Support\Webhooks\Contracts\WebhookRelay;

#[Alias('order.placed')]
class OrderPlaced implements Recordable, WebhookRelay
{
    use HasLoggable, HasRelays;
}
```

- `Recordable` → event gets logged
- `WebhookRelay` → relay row created for this channel
- `HasRelays` → discovers `WebhookRelay` in the event's interfaces

How `event_logs.data` gets populated (via `toLoggable()` / `Data`) is the event class's responsibility — not a concern of `webhooks`. This package reads from the already-stored plain array.

---

## End-to-End Flow

```
1. OrderPlaced dispatched
2. event-log creates Log (data stored as plain JSON via toLoggable())
3. Log processed → discovers WebhookRelay → creates Relay row
4. Relay processed → fires NeedsWebhookEnvelopes
5. GatherWebhookEnvelopes finds 2 subscriptions:
   - Sub A: version=V1, url='https://a.com/hook'
   - Sub B: version=V2, url='https://b.com/hook'
6. Adds 2 Envelopes: (recipient: SubA, version: V1), (recipient: SubB, version: V2)
7. event-log creates 2 Deliveries (payload is computed at access time, not stored):
   - Delivery 1: version='v1', recipient=SubA → payload resolves to $log->data['v1']
   - Delivery 2: version='v2', recipient=SubB → payload resolves to $log->data['v2']
8. Each Delivery processed → fires NeedsWebhookSent
9. SendWebhookDelivery POSTs payload to subscription URL with HMAC signature
```

---

## Standalone Usage (without api-first)

This package reads from `$log->data` (a plain array) at delivery time. It does not care how the data was structured or stored — that's the event class's responsibility (via `toLoggable()` / `Data`).

Subscriptions can:
- Leave `version` null → full `$log->data` as payload.
- Set `version` to one of the event's versions → that slice of `$log->data` as payload.

How the `version` value is stored and turned into a `Version` enum is the consuming app's call — webhooks only stores it and carries it to `Envelope::make()`. Whether `data` is flat or keyed by version is otherwise irrelevant to webhooks.

---

## Implementation Order

1. `WebhookRelay` interface (with `#[Dispatches]`)
2. `NeedsWebhookEnvelopes` event
3. `NeedsWebhookSent` event
4. `WebhookSubscription` model + migration
5. `GatherWebhookEnvelopes` listener
6. `SendWebhookDelivery` listener
7. Service provider (listener registration + migration boot)
8. Tests
