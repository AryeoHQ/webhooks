<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Webhooks;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Webhooks\Subscriptions\Builder\Builder;
use Support\Webhooks\Subscriptions\Factory;
use Support\Webhooks\Subscriptions\Subscription;

#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class ValidSubscription extends Subscription {}
