<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Support\Webhooks\Subscriptions\Builder\Builder;
use Support\Webhooks\Subscriptions\Status\Status;

/**
 * @property string $event
 * @property string $url
 * @property \Support\Events\Log\Logs\Data\Version\Contracts\Version|string|null $version
 * @property array<string, string>|null $headers
 * @property string $secret
 * @property \Support\Webhooks\Subscriptions\Status\Status $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Webhooks\Subscriptions\Status\Status> $status
 */
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Subscription extends Model
{
    use GeneratesSecret;

    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    /** @var class-string<self>|null */
    private static null|string $using = null;

    protected $table = 'webhook_subscriptions';

    protected $fillable = [
        'event',
        'url',
        'version',
        'headers',
    ];

    protected $attributes = [
        'status' => Status::Active,
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'status' => Status::class,
        ];
    }

    /**
     * @param  class-string<self>  $model
     */
    public static function use(string $model): void
    {
        self::$using = $model;
    }

    /**
     * @return class-string<self>
     */
    public static function using(): string
    {
        return self::$using ?? static::class;
    }

    public function setSubscriberAttribute(Model $subscriber): void
    {
        $this->attributes['subscriber_type'] = $subscriber->getMorphClass();
        $this->attributes['subscriber_id'] = $subscriber->getKey();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }
}
