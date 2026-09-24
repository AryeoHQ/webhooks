# Webhooks

Send recorded events as signed [CloudEvents](https://cloudevents.io/) over HTTP.
Built on [event-log](https://github.com/AryeoHQ/event-log).

## What this package does

It stores subscriptions, builds the CloudEvent, signs it, and sends it. It also
disables a subscription that keeps failing.

You write the listener that decides which subscriptions receive each event. Only
you know how to scope subscriptions to the right subscriber.

## What it does not do

| Concern | Where it belongs |
|---|---|
| Replay a delivery by hand | Your application. Automatic retries happen (see [Retries](#retries)). event-log gives you a retry trigger, but nothing calls it — build the command or endpoint you need. |
| Prune old delivery records | Your application. event-log owns those tables but ships no pruning, so schedule your own. Our one table holds subscriptions, which are configuration, not transient data. |
| Tell a subscriber they were disabled | Your application. We fire an event when a subscription is disabled. You choose the channel. |
| Verify a signature | The receiving application. See [Signature verification](#signature-verification) for the algorithm. |
| Keep webhook traffic away from internal services | Your infrastructure. See [Isolate webhook egress](#isolate-webhook-egress). |

---

## Install

```bash
composer require aryeo/webhooks
```

The service provider registers itself. The migration runs automatically.

---

## Use

Five steps to start sending webhooks.

### Step 1: Mark your event as a webhook

Add the `Webhook` interface and the `HasRelays` trait to any recordable event.

```php
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Support\Events\Log\Provides\HasRelays;
use Support\Webhooks\Contracts\Webhook;

#[Alias('article.updating')]
final class ArticleUpdating implements Webhook
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

When this event fires, event-log records it and creates a relay for the webhook
transport.

### Step 2: Define your version enum

Each subscription stores which payload version the subscriber wants. event-log
uses this to slice the right shape of data from the log. Define a `BackedEnum`
that implements the `Version` interface from event-log. In most applications
this maps to your API versions:

```php
use Support\Events\Log\Logs\Data\Version\Contracts\Version;

enum ApiVersion: string implements Version
{
    case V1 = 'v1';
    case V2 = 'v2';
}
```

The subscription's `version` column stores the string value (e.g. `'v1'`).

### Step 3: Write a collecting listener

The package sends the HTTP requests, but only you know who should receive them.
Write a listener that finds the right subscriptions and builds an envelope for
each one.

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
            ->each(fn (Subscription $subscription) => $event->add(
                Envelope::make(
                    recipient: $subscription,
                    version: $subscription->version ? ApiVersion::from($subscription->version) : null,
                ),
            ));
    }
}
```

`Envelope::make()` expects a `Version` enum or `null`. The column stores a
string, so you call `::from()` to convert it. If you extend the model (see
[Customize](#customize)), you can cast the column instead and skip the `::from()`
call.

### Step 4: Register the listener

Wire your collecting listener in a service provider:

```php
use Illuminate\Support\Facades\Event;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;

Event::listen(NeedsEnvelopes::class, GatherWebhookEnvelopes::class);
```

You do not register a sending listener. The package handles delivery.

### Step 5: Create subscriptions

A `Subscription` records who wants webhooks, at what URL, in what version. It
subscribes to one or more events.

```php
use Support\Webhooks\Subscriptions\Subscription;

$subscription = new Subscription;
$subscription->fill([
    'subscriber' => $organization,
    'url' => 'https://example.com/webhooks',
    'version' => ApiVersion::V1->value,
]);
$subscription->save();

$subscription->topics()->attach(['article.updating', 'article.deleting']);
```

The `secret` is generated automatically. Return it once to the subscriber so
they can verify signatures. You build the API or UI for managing subscriptions.

A subscription cannot subscribe to the same event twice — the pivot enforces a
unique constraint. Deleting a subscription detaches its topics via a listener.

That is everything you need. Events that implement `Webhook` are now delivered
as signed CloudEvents to every matching active subscription.

---

## What gets sent

### The request body

Every webhook is a [CloudEvents v1.0](https://github.com/cloudevents/spec)
structured-mode JSON message:

```json
{
  "specversion": "1.0",
  "id": "01966a3b-...",
  "source": "https://your-app.com",
  "type": "article.updating",
  "datacontenttype": "application/json",
  "time": "2026-09-01T12:00:00Z",
  "data": { ... }
}
```

### The request headers

| Header | Value |
|---|---|
| `Content-Type` | `application/cloudevents+json` |
| `Idempotency-Key` | The delivery id. Stable across retries. |
| `Source` | `config('app.url')`. Lets the receiver route before reading the body. |
| `Timestamp` | Unix timestamp of when the request was built. |
| `Signature` | HMAC-SHA256 hex digest. |

Custom headers from the subscription's `headers` column are included too. The
package sets its headers last, so a custom header cannot override them.

### Signature verification

The signature covers the timestamp and the body:

```
hash_hmac('sha256', "{$timestamp}.{$payload}", $secret)
```

A receiver verifies a webhook like this:

1. Read the `Timestamp` and `Signature` headers.
2. Compute `hash_hmac('sha256', "{$timestamp}.{$payload}", $secret)`.
3. Compare with `hash_equals()`.
4. Reject if the timestamp is too old (e.g. more than 5 minutes).

The timestamp is part of the signed material. An attacker who captures a request
cannot change the timestamp to look fresh without breaking the signature.

---

## Security

### Isolate webhook egress

A subscriber chooses the URL we deliver to. Route webhook traffic out a network
path with no route to your internal services.

Without this, a subscriber can point a subscription at an internal address and
make your workers send a signed request there. We record the response body on
the delivery attempt, so if subscribers can see their own delivery history they
can read what the internal service returned.

This is your infrastructure's job. A package cannot do it.

---

## Failure handling

### Retries

A non-2xx response throws. event-log retries the delivery based on the
`#[Tries]` attribute on the transport interface. Each retry creates a new
delivery attempt row.

### Auto-disable

When a subscription accumulates consecutive terminal delivery failures, the
package moves it to `Disabled`. The threshold is `WEBHOOKS_SUBSCRIPTION_FAILURE_THRESHOLD`
(default 10). Set to `0` to turn it off. A single successful delivery resets
the counter.

---

## Subscriptions in detail

### Schema

| Column | Type | Description |
|---|---|---|
| `id` | uuid | Primary key. |
| `subscriber_type` | string | Polymorphic owner type. |
| `subscriber_id` | uuid | Polymorphic owner id. |
| `url` | string | The delivery endpoint. |
| `version` | string, nullable | Which payload version to send. `null` sends the full payload. |
| `headers` | json, nullable | Custom headers to include with every delivery. |
| `secret` | string | HMAC signing secret, auto-generated. |
| `status` | string | `active`, `inactive`, or `disabled`. |

Topics live in `webhook_subscription_topics`, one row per subscribed topic:

| Column | Type | Description |
|---|---|---|
| `id` | uuid | Primary key. |
| `webhook_subscription_id` | uuid | FK to `webhook_subscriptions`. |
| `event_log_transportable_id` | string | FK to `event_log_transportables.id`. |

### Builder scopes

```php
Subscription::for('article.updating')        // has a topic with this alias
Subscription::active()                      // where status = active
Subscription::inactive()                    // where status = inactive
Subscription::disabled()                    // where status = disabled
```

### Status state machine

```
Active ──▶ Inactive    (user deactivates)
Active ──▶ Disabled    (system auto-disables)
Inactive ──▶ Active    (user reactivates)
Disabled ──▶ Active    (user reactivates)
```

```php
$subscription->status->deactivate()->now();
$subscription->status->activate()->now();
$subscription->status->disable()->now();
```

---

## Customize

Everything above works without customization. This section is optional.

### Extend the model

Both `Subscription` and `Topic` implement `Swappable`. To add casts,
relationships, or other behavior, create a subclass and register it in a service
provider.

```php
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Webhooks\Subscriptions\Builder\Builder;
use Support\Webhooks\Subscriptions\Collection\Subscriptions;
use Support\Webhooks\Subscriptions\Factory\Factory;
use Support\Webhooks\Subscriptions\Subscription as BaseSubscription;

#[CollectedBy(Subscriptions::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Subscription extends BaseSubscription
{
    protected $casts = [
        'version' => ApiVersion::class,
    ];
}
```

```php
// AppServiceProvider::boot()
\Support\Webhooks\Subscriptions\Subscription::use(App\Models\Subscription::class);
```

PHP does not inherit attributes. The subclass must redeclare `#[CollectedBy]`,
`#[UseEloquentBuilder]`, and `#[UseFactory]`. If any are missing, the sealed
traits throw `MissingAttribute` at runtime.

Use the `$casts` property, not the `casts()` method. The `Swapper` merges
properties from the parent and subclass — a method override would replace the
parent's casts instead of extending them.

**What this gives you.** With `version` cast to your enum, the collecting
listener gets simpler — no `::from()` call:

```php
Envelope::make(recipient: $subscription, version: $subscription->version)
```

---

## Configuration

| Variable | Default | Description |
|---|---|---|
| `WEBHOOKS_QUEUE_COLLECTING` | _(default)_ | Queue for the relay processing job. |
| `WEBHOOKS_QUEUE_SENDING` | _(default)_ | Queue for the delivery processing job. |
| `WEBHOOKS_TIMEOUT_CONNECT` | `5` | Seconds to wait for a TCP connection. |
| `WEBHOOKS_TIMEOUT_REQUEST` | `10` | Seconds to wait for the full request. |
| `WEBHOOKS_SUBSCRIPTION_FAILURE_THRESHOLD` | `10` | Consecutive failures before auto-disable. `0` to never auto-disable. |

---

## Further reading

- [Architecture](docs/architecture.md) — the Webhook object, CloudEvents
  structure, signing scheme, and queue resolution.
- [State machines](docs/state-machines.md) — subscription status transitions,
  triggers, and events.
