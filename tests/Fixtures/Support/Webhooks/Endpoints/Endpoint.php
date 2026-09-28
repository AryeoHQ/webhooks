<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Webhooks\Endpoints;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Webhooks\Endpoints\Builder\Builder;
use Support\Webhooks\Endpoints\Collection\Endpoints;
use Support\Webhooks\Endpoints\Endpoint as BaseEndpoint;
use Support\Webhooks\Endpoints\Factory\Factory;

#[CollectedBy(Endpoints::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class Endpoint extends BaseEndpoint {}
