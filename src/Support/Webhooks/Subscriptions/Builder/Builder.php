<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Support\Webhooks\Subscriptions\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Support\Webhooks\Subscriptions\Subscription>
 */
class Builder extends EloquentBuilder
{
    public function receiving(string $alias): self
    {
        return $this->where('event', $alias);
    }

    public function active(): self
    {
        return $this->where('status', Status::Active);
    }

    public function inactive(): self
    {
        return $this->where('status', Status::Inactive);
    }

    public function disabled(): self
    {
        return $this->where('status', Status::Disabled);
    }
}
