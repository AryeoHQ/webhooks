# Architecture

This document describes the Subscription model, the Webhook object, the
CloudEvents payload, and the signing scheme. For the subscription status states,
see [state-machines.md](state-machines.md).

## The transport interface

The `Webhook` interface at `Support\Webhooks\Contracts\Webhook` extends
event-log's `Transport`. It carries two attributes:

- `#[Dispatches]` — declares the collecting event (`NeedsEnvelopes`) and the
  sending event (`NeedsSent`). Event-log fires these at the right points in the
  relay pipeline.
- `#[Queues]` — declares the config keys for queue routing. The keys point into
  the `webhooks.queues` config, which is backed by environment variables.

The interface has no methods. An event opts in by implementing it.

## The Subscription model

`Support\Webhooks\Subscriptions\Subscription` is the recipient model for the
webhook transport. Event-log's `Delivery` holds a polymorphic reference to it as
the `recipient`.

### Table: `webhook_subscriptions`

| Column | Type | Description |
|---|---|---|
| `id` | uuid | Primary key. |
| `subscriber_type` | string | Polymorphic owner type (e.g. `Organization`). |
| `subscriber_id` | uuid | Polymorphic owner id. |
| `event` | string | The `#[Alias]` value this subscription receives. Indexed. |
| `url` | string | The endpoint the webhook is POSTed to. Read at send time, not at creation time — if the URL changes, the next delivery uses the new value. |
| `version` | string, nullable | Which payload version the subscriber wants. Passed through to `Envelope::make()`. `null` means the full payload. |
| `headers` | json, nullable | Custom HTTP headers merged into every delivery. |
| `secret` | string | HMAC-SHA256 signing key. Auto-generated when the model is instantiated. Not mass-assignable. |
| `status` | string | The subscription's status: `active`, `inactive`, or `disabled`. See [state-machines.md](state-machines.md). |

### Secret generation

The `GeneratesSecret` trait runs an `initializeGeneratesSecret` method on every
instantiation. If `secret` is not already set, it generates a 64-character random
string. An explicitly provided secret is preserved.

The secret is not mass-assignable. The consumer decides how and when to expose it
(for example, returning it once in the API response that creates the
subscription).

### The subscriber relationship

The `subscriber` is a `MorphTo` relationship. The columns are not mass-assignable.
Use the mutator:

```php
$subscription->subscriber = $organization;
```

This sets `subscriber_type` and `subscriber_id` from the model.

### Builder scopes

The custom `Builder` at `Support\Webhooks\Subscriptions\Builder\Builder` provides
four scopes:

| Scope | SQL |
|---|---|
| `for($alias)` | `where event = $alias` |
| `active()` | `where status = 'active'` |
| `inactive()` | `where status = 'inactive'` |
| `disabled()` | `where status = 'disabled'` |

The collecting listener chains these to find the subscriptions that should receive
a given event.

## The Webhook object

`Support\Webhooks\Webhooks\Webhook` is a value object that wraps a `Delivery`
into a CloudEvent. All of its public properties are lazy cached getters on the
delivery.

| Property | Type | Source |
|---|---|---|
| `id` | `string` | The delivery id. This is the idempotency key. |
| `source` | `string` | `config('app.url')`. |
| `type` | `string` | The `#[Alias]` value from the event (`$delivery->relay->log->type`). |
| `data` | `array\|null` | The resolved payload (`$delivery->payload`). |
| `time` | `CarbonImmutable` | When the event was dispatched (`$delivery->relay->log->occurred_at`). |
| `payload` | `string` | The serialized CloudEvents JSON string. |
| `headers` | `array` | The `Idempotency-Key`, `X-Webhook-Signature`, and custom subscription headers. |

The object is created with `Webhook::make($delivery)`. The constructor is private.

### `deliver()`

`Webhook::deliver()` POSTs the payload to the subscription's URL with the
computed headers and a content type of `application/cloudevents+json`. It returns
the `Illuminate\Http\Client\Response`.

## The CloudEvents payload

Every webhook is a [CloudEvents v1.0](https://github.com/cloudevents/spec/blob/v1.0.2/cloudevents/spec.md)
structured-mode JSON message. The `cloudevents/sdk-php` package handles
serialization.

```json
{
  "specversion": "1.0",
  "id": "01966a3b-...",
  "source": "https://your-app.com",
  "type": "article.updated",
  "datacontenttype": "application/json",
  "time": "2026-09-01T12:00:00+00:00",
  "data": { "id": "...", "title": "..." }
}
```

The `data` field holds the payload slice for the subscription's requested
version. If no version was requested, it holds the full `event_logs.data`
snapshot.

## Signing

Every request carries an `X-Webhook-Signature` header. The value is an
HMAC-SHA256 hash of the serialized JSON body, keyed by the subscription's
`secret`.

```
X-Webhook-Signature: hash_hmac('sha256', $body, $subscription->secret)
```

The receiver can verify the signature by computing the same hash and comparing.
The secret is generated once and stored on the subscription. The package does not
rotate secrets — the consumer decides the rotation strategy.

## Idempotency

The `Idempotency-Key` header carries the delivery id. This id is stable across
every attempt and retry of the same delivery. Event-log's internal deduplication
prevents duplicate deliveries on our side, but the HTTP POST is the one hop a
database cannot make idempotent. A receiver that honours the header can drop
duplicates.

## Queue resolution

The `#[Queues]` attribute on the `Webhook` transport holds config keys, not queue
names:

- `collecting` → `webhooks.queues.collecting` → `WEBHOOKS_COLLECTING_QUEUE`
- `sending` → `webhooks.queues.sending` → `WEBHOOKS_SENDING_QUEUE`

If the key is unset, event-log falls back to the layer's `EVENT_LOG_QUEUE_*`
config, then the framework default. The consumer sets the env variable to route
webhook jobs to a dedicated queue.

## The sending listener

`Support\Webhooks\Sending\Listeners\Deliver` is three lines:

```php
$response = Webhook::make($event->delivery)->deliver();
$event->result($response->body());
$response->throw();
```

1. Build the webhook and POST it.
2. Record the response body on the delivery attempt.
3. Throw on non-2xx — the exception propagates through event-log's failure
   pipeline.

The package registers this listener automatically. The consumer does not wire it.

## The collecting listener

The package does **not** provide a collecting listener. The consumer writes one.

The collecting listener receives a `NeedsEnvelopes` event, which holds the
`Relay`. The relay holds the `Log`, which holds the event alias and the loggable
model. The consumer queries subscriptions with their own scoping (by subscriber,
by tenant, by any criteria) and adds an `Envelope` for each.

The package gives the consumer the tools to query: `Subscription::for($alias)->active()`.
The scoping beyond that is the consumer's domain.
## Auto-disable on repeated failure

`Support\Webhooks\Subscriptions\Listeners\AutoDisable` listens for
event-log's delivery `Failed` after-event. When a delivery terminally fails (all
tries exhausted), the listener counts consecutive failures for that subscription
and disables it when the count reaches the configured threshold.

The count is "consecutive" — it counts `Failed` deliveries whose `updated_at` is
after the most recent `Succeeded` delivery for the same subscription. If there
has never been a successful delivery, all failures count.

The threshold is `config('webhooks.failure.threshold')`, backed by
`WEBHOOKS_FAILURE_THRESHOLD` (default 10). Set it to 0 to turn off auto-disable
entirely.

The listener skips:

- Recipients that are not a `Subscription` (the `Failed` event fires for all
  transports, not just webhooks).
- Subscriptions that are already `Inactive` or `Disabled`.

The package registers this listener automatically.
