<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Listeners;

use Support\Webhooks\Subscriptions\Events\Deleting;

class Cleanup
{
    public function handle(Deleting $event): void
    {
        $event->subscription->topics()->detach();
    }
}
