<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Webhooks;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Events\Log\Transportables\Builder;
use Support\Events\Log\Transportables\Collection\Transportables;
use Support\Events\Log\Transportables\Factory;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Transportables\Scopes\Webhookable;

#[CollectedBy(Transportables::class)]
#[ScopedBy(Webhookable::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class WebhookEvent extends Transportable {}
