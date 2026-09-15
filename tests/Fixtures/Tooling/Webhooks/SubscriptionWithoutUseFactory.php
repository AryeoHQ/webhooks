<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Webhooks;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Support\Webhooks\Subscriptions\Builder\Builder;
use Support\Webhooks\Subscriptions\Subscription;

#[UseEloquentBuilder(Builder::class)]
final class SubscriptionWithoutUseFactory extends Subscription {}
