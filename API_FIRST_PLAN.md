# api-first — Data Adoption Plan

## Overview

`aryeo/api-first` provides `LogsSchemas` which implements `toLoggable()` on behalf of event classes. The `LogsSchemas` trait must produce `Data` instances containing one `Variant` per schema version.

**Dependency:** `event-log` ← `api-first`

---

## The event-log Data API

`Loggable::toLoggable()` returns `Support\Events\Log\Logs\Data\Data`.

```php
// Data::of() takes a variadic list of Variants
Data::of(Variant ...$variants): Data

// Variant::make() takes an Arrayable/JsonSerializable/Jsonable payload and a Version enum
Variant::make(Arrayable|JsonSerializable|Jsonable $payload, null|Version $version = null): Variant

// Version is a marker over BackedEnum
interface Version extends BackedEnum {}
```

`Data` serializes to JSON keyed by each variant's `version->value` (e.g. `{"v1": {...}, "v2": {...}}`). `Data` is also an Eloquent `Castable` — the `event_logs.data` column stores this JSON and reads it back as a plain array.

`Variant::make()` auto-discovers version from the payload: if the payload object has a public `Version`-typed property, that value is used (and wins over the explicit `$version` argument). If neither is present, `NotProvided` is thrown.

---

## What `LogsSchemas` Must Do

The trait provides the `toLoggable()` implementation for event classes. It needs to:

1. Collect schema versions (as it does today)
2. Wrap each version's payload as a `Variant` via `Variant::make($payload, $version)` — where `$payload` is an `Arrayable`/`JsonSerializable`/`Jsonable` object and `$version` is a `Version` enum case
3. Return `Data::of(...$variants)`

```php
public function toLoggable(): Data
{
    return Data::of(
        Variant::make($this->toV1Schema(), PayloadVersion::V1),
        Variant::make($this->toV2Schema(), PayloadVersion::V2),
    );
}
```

The consuming app defines its own `PayloadVersion` enum implementing `Version`:

```php
enum PayloadVersion: string implements Version
{
    case V1 = 'v1';
    case V2 = 'v2';
}
```

The backed values (`'v1'`, `'v2'`) become the JSON keys in `event_logs.data` and the values that webhook subscriptions reference in their `version` column.

---

## What Doesn't Change

- `api-first` has **no delivery-time involvement** — it only structures data at log time
- Schema resolution logic (which versions to include, how to build each version's payload) stays the same
- The downstream package chain (`api-first` ← Application) is unaffected in structure

---

## Key Points

- `api-first` expresses its opinion at log time by structuring `data` with versioned `Variant`s inside a `Data` instance
- Version values in `api-first` correspond to API versions (e.g., `'v1'`, `'v2'`, `'2024-01-15'`) via a `BackedEnum`
- The application sets `version` on webhook subscriptions to select which variant a subscriber receives
- `api-first` does not participate in delivery — it has no listeners, no relay awareness
