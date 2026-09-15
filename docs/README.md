# Webhooks

This package is a pre-built event-log transport for webhook delivery. It gives
the consumer a subscription model, an HMAC-signed HTTP sender, and a status
state machine. This document gives the full picture. The other documents give
the detail.

## What the package provides

The package provides five things:

1. A `Webhook` transport interface. An event that implements this interface
   enters the webhook delivery pipeline when it is recorded.
2. A `Subscription` model. It stores who wants to receive which events, at what
   URL, with what payload version.
3. A sending listener. It builds a CloudEvents payload from the delivery, signs
   it with HMAC-SHA256, and POSTs it to the subscription's URL.
4. A `Webhook` value object. It wraps a delivery into a CloudEvent and owns the
   serialization, headers, and the HTTP call.
5. Auto-disable. When a subscription accumulates enough consecutive delivery
   failures, the package moves it to `Disabled` automatically.

## What the consumer provides

The consumer provides three things:

1. A collecting listener. It queries subscriptions with the consumer's own
   scoping logic and builds envelopes. The package cannot know which subscribers
   should receive which events — that is the consumer's domain.
2. The mechanism for creating subscriptions. An API, a UI, a console command —
   however the consumer lets their users manage webhook subscriptions.
3. Event opt-in. Each event class implements the `Webhook` interface and uses the
   `HasRelays` trait. The event must already be `Recordable`.

## How the pipeline runs

The webhook pipeline is a specialization of event-log's relay system. The flow
looks like this:

```
Event dispatched
  │
  ▼
Log (event-log records the event)
  │
  ▼
Relay (event-log creates one relay for the Webhook transport)
  │
  ▼
NeedsEnvelopes (the consumer's collecting listener runs)
  │
  ▼
Delivery (event-log creates one per envelope / per subscription)
  │
  ▼
NeedsSent → Deliver → Webhook::make($delivery)->deliver()
  │
  ▼
HTTP POST (CloudEvents JSON, HMAC-signed)
```

Everything above the `NeedsEnvelopes` line is automatic. The consumer writes the
collecting listener. Everything below it is automatic.

## Where to read next

| Document | Subject |
|---|---|
| [architecture.md](architecture.md) | The Subscription model, the Webhook object, the CloudEvents payload, and the signing scheme |
| [state-machines.md](state-machines.md) | The subscription status states, transitions, and triggers |
