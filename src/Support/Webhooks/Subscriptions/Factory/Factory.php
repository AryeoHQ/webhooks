<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Factory;

use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Subscriptions\Subscription;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Webhooks\Subscriptions\Subscription>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    use SealsModelName;

    final protected $model { get => Subscription::using(); }

    /**
     * @return array<string, mixed>
     */
    final public function definition(): array
    {
        return [
            'event_log_transportable_id' => Transportable::factory()->create()->id,
        ];
    }
}
