<?php

declare(strict_types=1);

namespace Support\Webhooks\Sending\Listeners;

use Support\Webhooks\Sending\Events\NeedsSent;
use Support\Webhooks\Webhooks\Webhook;

class Deliver
{
    public function handle(NeedsSent $event): void
    {
        $response = Webhook::make($event->delivery)->deliver();

        $event->result($response->body());

        $response->throw();
    }
}
