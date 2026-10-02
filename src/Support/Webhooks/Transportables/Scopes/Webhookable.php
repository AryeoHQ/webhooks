<?php

declare(strict_types=1);

namespace Support\Webhooks\Transportables\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Support\Webhooks\Contracts\Webhook;

final class Webhookable implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->transportedByAny(Webhook::class); // @phpstan-ignore method.notFound
    }
}
