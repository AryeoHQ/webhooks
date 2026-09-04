<?php

declare(strict_types=1);

namespace Support\Webhooks\Collecting\Events;

use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Transports\Dispatches\Collecting\Provides\CollectsEnvelopes;

class NeedsEnvelopes implements \Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes
{
    use CollectsEnvelopes;

    public readonly Relay $relay;

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }
}
