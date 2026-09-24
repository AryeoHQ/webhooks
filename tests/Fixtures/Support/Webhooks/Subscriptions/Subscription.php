<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Webhooks\Subscriptions;

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
final class Subscription extends BaseSubscription {}
