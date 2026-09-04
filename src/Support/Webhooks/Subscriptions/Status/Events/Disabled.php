<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Status\Events;

use Support\Webhooks\Subscriptions\Subscription;

class Disabled
{
    public readonly Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
