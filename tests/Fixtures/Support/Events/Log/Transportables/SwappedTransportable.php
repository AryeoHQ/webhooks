<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Events\Log\Transportables;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Events\Log\Transportables\Builder;
use Support\Events\Log\Transportables\Collection\Transportables;
use Support\Events\Log\Transportables\Factory;
use Support\Events\Log\Transportables\Transportable;

#[CollectedBy(Transportables::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class SwappedTransportable extends Transportable
{
    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'deleting' => Events\Deleting::class,
    ];
}
