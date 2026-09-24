<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Factory;

use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Topics\Topic;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Webhooks\Topics\Topic>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    use SealsModelName;

    final protected $model { get => Topic::using(); }

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
