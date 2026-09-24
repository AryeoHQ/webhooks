<?php

declare(strict_types=1);

namespace Support\Webhooks\Sending\Listeners;

use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Support\Events\Log\Transports\Dispatches\Sending\Results\Result;
use Support\Webhooks\Sending\Events\NeedsSent;
use Support\Webhooks\Webhooks\Webhook;
use Symfony\Component\HttpFoundation\Response;

class Deliver
{
    public function handle(NeedsSent $event): void
    {
        $response = Webhook::make($event->delivery)->deliver();

        $event->record(
            Result::make(message: $response->body(), code: $response->status())
        );

        match ($response->status()) {
            Response::HTTP_GONE, Response::HTTP_MISDIRECTED_REQUEST => throw new Undeliverable("HTTP {$response->status()}"),
            default => $response->throw(),
        };
    }
}
