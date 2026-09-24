<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Builder;

use Support\Webhooks\Subscriptions\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Support\Webhooks\Subscriptions\Subscription>
 */
class Builder extends \Illuminate\Database\Eloquent\Builder
{
    final public function for(string $alias): self
    {
        return $this->whereHas(
            'topics', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('event_log_transportable_id', $alias)
        );
    }

    final public function active(): self
    {
        return $this->where('status', Status::Active);
    }

    final public function inactive(): self
    {
        return $this->where('status', Status::Inactive);
    }

    final public function disabled(): self
    {
        return $this->where('status', Status::Disabled);
    }
}
