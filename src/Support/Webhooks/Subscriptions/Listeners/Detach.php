<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Listeners;

use Support\Events\Log\Transportables\Events\Deleting;

class Detach
{
    public function handle(Deleting $event): void
    {
        $event->transportable->webhookEndpoints()->detach(); // @phpstan-ignore method.notFound
    }
}
