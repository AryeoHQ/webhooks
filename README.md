# Webhooks

A pre-built [event-log](https://github.com/AryeoHQ/event-log) transport that
delivers recorded events as [CloudEvents](https://cloudevents.io/) over HTTP.
The package gives you a `Subscription` model, an HMAC-signed delivery pipeline,
and a status state machine. You supply the collecting listener that decides which
subscriptions receive each event.

## Installation

```bash
composer require aryeo/webhooks
```

The service provider auto-registers.

## Overview

The package does two things:

1. It stores webhook subscriptions — who wants to receive which events, at what
   URL, with what payload version.
2. It delivers those events as CloudEvents over HTTP, with an HMAC signature and
   an idempotency key.

You do two things:

1. Write a collecting listener that queries subscriptions and builds envelopes.
   Only you know how to scope subscriptions to the right subscriber.
2. Create the API or UI that lets your users manage their subscriptions.

---

## Making an event deliverable

### 1. Implement the transport interface

Mark your event with the `Webhook` interface and use the `HasRelays` trait.

```php
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Support\Events\Log\Provides\HasRelays;
use Support\Webhooks\Contracts\Webhook;

#[Alias('article.updated')]
final class ArticleUpdated implements Webhook, RecordableAfterCommit
{
    use HasLoggable;
    use HasRelays;

    #[IdentifiesLoggable]
    public Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
```

`HasRelays` discovers the `Webhook` interface and tells event-log to create a
relay for this transport when the event is recorded.

### 2. Write a collecting listener

The package sends webhooks, but it does not decide who receives them. You write
a listener for `NeedsEnvelopes` that finds the subscriptions for the event and
scopes them to the subscriber (an organization, a team, an integration —
whatever your domain uses).

```php
use Support\Events\Log\Envelopes\Envelope;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;
use Support\Webhooks\Subscriptions\Subscription;

final class GatherWebhookEnvelopes
{
    public function handle(NeedsEnvelopes $event): void
    {
        Subscription::for($event->relay->log->type)->active()
            ->where('subscriber_type', Organization::class)
            ->where('subscriber_id', $event->relay->log->loggable->organization_id)
            ->each(fn (Subscription $sub) => $event->add(
                Envelope::make(recipient: $sub, version: $sub->version),
            ));
    }
}
```

### 3. Register the listener

Wire your collecting listener in a service provider:

```php
use Illuminate\Support\Facades\Event;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;

Event::listen(NeedsEnvelopes::class, GatherWebhookEnvelopes::class);
```

You do not need to register a sending listener. The package handles delivery.

---

## Subscriptions

A `Subscription` is an Eloquent model that records who wants to receive which
events at what URL.

### Creating a subscription

```php
use Support\Webhooks\Subscriptions\Subscription;

$subscription = new Subscription;
$subscription->subscriber = $organization;
$subscription->fill([
    'event' => 'article.updated',
    'url' => 'https://example.com/webhooks',
    'version' => 'v1',
]);
$subscription->save();
```

The `secret` is auto-generated at instantiation. You do not need to set it.
Return it once to the subscriber so they can verify signatures.

### Schema

| Column | Type | Description |
|---|---|---|
| `id` | uuid | Primary key. |
| `subscriber_type` | string | Polymorphic owner type. |
| `subscriber_id` | uuid | Polymorphic owner id. |
| `event` | string | The event alias (e.g. `article.updated`). |
| `url` | string | The delivery endpoint. |
| `version` | string, nullable | Which payload version to send. `null` sends the full payload. |
| `headers` | json, nullable | Custom headers to include with every delivery. |
| `secret` | string | HMAC signing secret, auto-generated. |
| `status` | string | `active`, `inactive`, or `disabled`. |

### Builder scopes

```php
Subscription::for('article.updated')         // where event = ...
Subscription::active()                      // where status = active
Subscription::inactive()                    // where status = inactive
Subscription::disabled()                    // where status = disabled
```

### Status state machine

A subscription starts `Active`. It can be deactivated by the user or disabled
by the system when deliveries keep failing.

```
Active ──▶ Inactive    (user deactivates)
Active ──▶ Disabled    (system disables)
Inactive ──▶ Active    (user reactivates)
Disabled ──▶ Active    (user reactivates)
```

```php
$subscription->status->deactivate()->now();  // Active → Inactive
$subscription->status->activate()->now();    // Inactive → Active
$subscription->status->disable()->now();     // Active → Disabled
```

---

## The delivery

When event-log processes a delivery for the webhook transport, the `Deliver`
listener builds a `Webhook` object from the delivery and POSTs it.

### CloudEvents format

Every webhook is a [CloudEvents v1.0](https://github.com/cloudevents/spec)
structured-mode JSON message:

```json
{
  "specversion": "1.0",
  "id": "01966a3b-...",
  "source": "https://your-app.com",
  "type": "article.updated",
  "datacontenttype": "application/json",
  "time": "2026-09-01T12:00:00Z",
  "data": { ... }
}
```

| Field | Source |
|---|---|
| `id` | The delivery id. Stable across retries (the idempotency key). |
| `source` | `config('app.url')`. |
| `type` | The `#[Alias]` string from the event. |
| `data` | The payload slice for the subscription's version, or the full payload. |
| `time` | When the event was dispatched. |

### Headers

Every request includes:

| Header | Value |
|---|---|
| `Content-Type` | `application/cloudevents+json` |
| `Idempotency-Key` | The delivery id. |
| `X-Webhook-Signature` | HMAC-SHA256 of the body, keyed by the subscription's secret. |

Custom headers from the `Subscription` are also included.

### Failure and retries

A non-2xx response throws. The exception propagates through event-log's
delivery attempt pipeline. The `#[Tries]` attribute on the `Webhook` transport
controls how many attempts each delivery gets. On the terminal attempt, the
delivery moves to `Failed`.

### Auto-disable on repeated failure

When a subscription accumulates enough consecutive terminal delivery failures,
the package moves it to `Disabled` automatically. A terminal failure is a
delivery that exhausted all its tries and landed on `Failed`.

The threshold is controlled by `WEBHOOKS_FAILURE_THRESHOLD` (default 10). Set it
to 0 to never auto-disable. A single successful delivery resets the counter.

The listener skips subscriptions that are already `Inactive` or `Disabled`.

---

## Configuration

| Variable | Default | Description |
|---|---|---|
| `WEBHOOKS_COLLECTING_QUEUE` | _(default)_ | Queue for the relay processing job. |
| `WEBHOOKS_SENDING_QUEUE` | _(default)_ | Queue for the delivery processing job. |
| `WEBHOOKS_FAILURE_THRESHOLD` | `10` | Consecutive terminal failures before a subscription is auto-disabled. `0` to never auto-disable. |

Queue keys are referenced by the `#[Queues]` attribute on the transport
interface. An unset key falls back to event-log's layer queue, then the framework
default.

---

## Architecture

Design documentation lives in [docs/](docs/):

- [docs/README.md](docs/README.md) — what the package provides, what the
  consumer provides, and the end-to-end flow.
- [docs/architecture.md](docs/architecture.md) — the Subscription model, the
  Webhook object, the CloudEvents payload, and the signing scheme.
- [docs/state-machines.md](docs/state-machines.md) — the subscription status
  states, transitions, and triggers.
