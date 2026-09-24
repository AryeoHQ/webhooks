<?php

declare(strict_types=1);

namespace Support\Webhooks\Sending\Events;

use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Transports\Dispatches\Sending\Provides\RecordsResult;

class NeedsSent implements \Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent
{
    use RecordsResult;

    public readonly Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }
}
