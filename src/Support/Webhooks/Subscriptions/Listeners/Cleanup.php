<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Listeners;

use Support\Webhooks\Endpoints\Events\Deleting;

class Cleanup
{
    public function handle(Deleting $event): void
    {
        $event->endpoint->events()->detach();
    }
}
