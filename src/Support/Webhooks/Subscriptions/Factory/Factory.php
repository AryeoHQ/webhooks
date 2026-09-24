<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Factory;

use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;
use Support\Webhooks\Subscriptions\Status\Status;
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
            'url' => fake()->url(),
            'secret' => \Illuminate\Support\Str::random(64),
        ];
    }

    final public function active(): self
    {
        return $this->state(['status' => Status::Active]);
    }

    final public function inactive(): self
    {
        return $this->state(['status' => Status::Inactive]);
    }

    final public function disabled(): self
    {
        return $this->state(['status' => Status::Disabled]);
    }
}
