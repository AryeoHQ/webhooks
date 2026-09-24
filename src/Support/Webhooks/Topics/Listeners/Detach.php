<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Listeners;

use Support\Events\Log\Transportables\Events\Deleting;

class Detach
{
    public function handle(Deleting $event): void
    {
        $event->transportable->webhookSubscriptions()->detach(); // @phpstan-ignore method.notFound
    }
}
