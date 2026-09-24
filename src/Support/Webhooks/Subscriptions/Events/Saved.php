<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Events;

use Support\Webhooks\Subscriptions\Subscription;

class Saved
{
    final public readonly Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
