<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Webhooks;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Webhooks\Subscriptions\Factory;
use Support\Webhooks\Subscriptions\Subscription;

#[UseFactory(Factory::class)]
final class SubscriptionWithoutUseEloquentBuilder extends Subscription {}
