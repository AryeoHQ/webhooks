<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Listeners;

use Support\Webhooks\Endpoints\Events\Deleting;

class Cleanup
{
    public function handle(Deleting $event): void
    {
        $event->endpoint->topics()->detach();
    }
}
