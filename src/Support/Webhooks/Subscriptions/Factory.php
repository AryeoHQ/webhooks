<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Support\Webhooks\Subscriptions\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Webhooks\Subscriptions\Subscription>
 */
final class Factory extends EloquentFactory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => fake()->slug(2, false).'.'.fake()->randomElement(['created', 'updated', 'deleted']),
            'url' => fake()->url(),
        ];
    }

    public function active(): self
    {
        return $this->state(['status' => Status::Active]);
    }

    public function inactive(): self
    {
        return $this->state(['status' => Status::Inactive]);
    }

    public function disabled(): self
    {
        return $this->state(['status' => Status::Disabled]);
    }
}
