<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Subscriber;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\Logs\Data\Data;
use Support\Events\Log\Logs\Data\Variant;
use Tests\Fixtures\Support\Concerns\AnnouncesToLog;

/**
 * @property string $id
 */
#[UseFactory(Factory::class)]
class Subscriber extends Model implements Loggable
{
    use AnnouncesToLog;

    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'creating' => Events\Creating::class,
        'updated' => Events\Updated::class,
    ];

    public function toLoggable(): Data
    {
        return Data::of(Variant::make($this, version: PayloadVersion::V1));
    }
}
