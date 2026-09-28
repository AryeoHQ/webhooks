# State Machines

This document describes the endpoint status states, transitions, and
triggers. For the model and its columns, see
[architecture.md](architecture.md).

## Endpoint status

An endpoint has one status column driven by the `Status` enum at
`Support\Webhooks\Endpoints\Status\Status`.

### States

| State | Meaning |
|---|---|
| **Active** | The endpoint is live. Deliveries go out. |
| **Inactive** | The user paused the endpoint. No deliveries. |
| **Disabled** | The system turned the endpoint off (repeated delivery failures). No deliveries. |

A new endpoint starts `Active`.

### Transitions

```
         deactivate()           activate()
Active ──────────────▶ Inactive ──────────────▶ Active
   │                                              ▲
   │ disable()                        activate()  │
   ▼                                              │
Disabled ─────────────────────────────────────────┘
```

| From | To | Trigger | Who initiates |
|---|---|---|---|
| Active | Inactive | `Deactivate` | The user |
| Active | Disabled | `Disable` | The system |
| Inactive | Active | `Activate` | The user |
| Disabled | Active | `Activate` | The user |

### Usage

```php
$endpoint->status->deactivate()->now();  // Active → Inactive
$endpoint->status->activate()->now();    // Inactive or Disabled → Active
$endpoint->status->disable()->now();     // Active → Disabled
```

### Events

Each state fires a before and after event:

| State | Before | After |
|---|---|---|
| Active | `Activating` | `Activated` |
| Inactive | `Deactivating` | `Deactivated` |
| Disabled | `Disabling` | `Disabled` |

All events live in `Support\Webhooks\Endpoints\Status\Events` and hold a
single `Endpoint` property.

### Triggers

All triggers live in `Support\Webhooks\Endpoints\Status\Triggers`. Each
extends the `Trigger` base class from `aryeo/eloquent-state-machines` and
targets the `Endpoint` model via `#[Target]`.

| Trigger | Handle |
|---|---|
| `Activate` | No-op. The transition itself is the work. |
| `Deactivate` | No-op. The transition itself is the work. |
| `Disable` | No-op. The transition itself is the work. |

The triggers are empty because the status change is the entire operation. If
the consumer needs side effects (send a notification when disabled, log when
reactivated), they listen for the after events.

### Auto-disable

The `Active → Disabled` transition is driven automatically by the
`AutoDisable` listener. When a delivery terminally fails, the
listener counts consecutive failures for the endpoint. If the count reaches
`WEBHOOKS_ENDPOINT_FAILURE_THRESHOLD` (default 10), it calls
`$endpoint->status->disable()->now()`.

A successful delivery resets the counter. Set the threshold to 0 to turn off
auto-disable. See [architecture.md](architecture.md#auto-disable-on-repeated-failure)
for the counting logic.

### Querying by status

The custom builder provides a scope for each state:

```php
Endpoint::active()    // where status = 'active'
Endpoint::inactive()  // where status = 'inactive'
Endpoint::disabled()  // where status = 'disabled'
```

The collecting listener uses `active()` to skip paused and disabled
endpoints.
