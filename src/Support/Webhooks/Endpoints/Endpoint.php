<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Support\Events\Database\Eloquent\Swappable\Models\Concerns\SupportsSwapping;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Endpoints\Builder\Builder;
use Support\Webhooks\Endpoints\Collection\Endpoints;
use Support\Webhooks\Endpoints\Factory\Factory;
use Support\Webhooks\Endpoints\Status\Status;
use Support\Webhooks\Subscriptions\Subscription;

/**
 * @property string $subscriber_type
 * @property string $subscriber_id
 * @property string $url
 * @property \Support\Events\Log\Logs\Data\Version\Contracts\Version|string|null $version
 * @property array<string, string>|null $headers
 * @property string $secret
 * @property \Support\Webhooks\Endpoints\Status\Status $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Webhooks\Endpoints\Status\Status> $status
 */
#[CollectedBy(Endpoints::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Endpoint extends Model implements Swappable
{
    use GeneratesSecret;
    use HasUuids {
        getKeyType as private uuidKeyType;
        getIncrementing as private uuidIncrementing;
    }

    /** @use SupportsSwapping<Factory, Builder> */
    use SupportsSwapping;

    final public $incrementing = false;

    final protected $table = 'webhook_endpoints';

    final protected $primaryKey = 'id';

    final protected $keyType = 'string';

    final public function getKeyType(): string
    {
        return $this->uuidKeyType();
    }

    final public function getIncrementing(): bool
    {
        return $this->uuidIncrementing();
    }

    protected $fillable = [
        'subscriber',
        'url',
        'version',
        'headers',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $attributes = [
        'status' => Status::Active,
    ];

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'retrieved' => Events\Retrieved::class,
        'creating' => Events\Creating::class,
        'created' => Events\Created::class,
        'updating' => Events\Updating::class,
        'updated' => Events\Updated::class,
        'saving' => Events\Saving::class,
        'saved' => Events\Saved::class,
        'replicating' => Events\Replicating::class,
        'deleting' => Events\Deleting::class,
        'deleted' => Events\Deleted::class,
    ];

    /**
     * @var array<string, class-string|string>
     */
    protected $casts = [
        'headers' => 'array',
        'status' => Status::class,
    ];

    public function setSubscriberAttribute(Model $subscriber): void
    {
        $this->attributes['subscriber_type'] = $subscriber->getMorphClass();
        $this->attributes['subscriber_id'] = $subscriber->getKey();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    final public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\Support\Events\Log\Transportables\Transportable, $this, \Support\Webhooks\Subscriptions\Subscription, 'subscription'>
     */
    final public function events(): BelongsToMany
    {
        return $this->belongsToMany(Transportable::using(), 'webhook_subscriptions', 'webhook_endpoint_id', 'event_log_transportable_id')
            ->using(Subscription::using())
            ->as('subscription')
            ->withTimestamps();
    }
}
