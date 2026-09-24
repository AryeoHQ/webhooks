<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Subscriber\Events;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\Deliveries\Tries\Tries;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Support\Events\Log\Provides\HasRelays;
use Support\Webhooks\Contracts\Webhook;
use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;

#[Alias('subscriber.updated')]
#[Tries(3)]
final class Updated implements RecordableAfterCommit, Webhook
{
    use HasLoggable;
    use HasRelays;

    #[IdentifiesLoggable]
    public Subscriber $subscriber;

    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }
}
