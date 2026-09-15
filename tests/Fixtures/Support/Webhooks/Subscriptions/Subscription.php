<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Webhooks\Subscriptions;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Webhooks\Subscriptions\Builder\Builder;
use Support\Webhooks\Subscriptions\Factory;
use Support\Webhooks\Subscriptions\Subscription as BaseSubscription;

// PHP does not inherit attributes, so a subclass must redeclare both of these.
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class Subscription extends BaseSubscription {}
