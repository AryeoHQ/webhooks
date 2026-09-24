<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Support\Events\Database\Eloquent\Swappable\Models\Concerns\SupportsSwapping;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable;
use Support\Events\Log\Transportables\Transportable;
use Support\Webhooks\Subscriptions\Subscription;
use Support\Webhooks\Topics\Builder\Builder;
use Support\Webhooks\Topics\Collection\Topics;
use Support\Webhooks\Topics\Factory\Factory;

/**
 * @property string $event_log_transportable_id
 * @property string $webhook_subscription_id
 */
#[CollectedBy(Topics::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Topic extends Pivot implements Swappable
{
    use HasUuids {
        getKeyType as private uuidKeyType;
        getIncrementing as private uuidIncrementing;
    }

    /** @use SupportsSwapping<Factory, Builder> */
    use SupportsSwapping;

    final public $incrementing = false;

    final protected $table = 'webhook_subscription_topics';

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
        'event_log_transportable_id',
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Support\Webhooks\Subscriptions\Subscription, $this>
     */
    final public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::using(), 'webhook_subscription_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Support\Events\Log\Transportables\Transportable, $this>
     */
    final public function transportable(): BelongsTo
    {
        return $this->belongsTo(Transportable::using(), 'event_log_transportable_id');
    }
}
