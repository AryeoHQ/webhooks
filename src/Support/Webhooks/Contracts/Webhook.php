<?php

declare(strict_types=1);

namespace Support\Webhooks\Contracts;

use Support\Events\Log\Deliveries\Tries\Tries;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Support\Events\Log\Transports\Dispatches\Queues;
use Support\Webhooks\Collecting\Events\NeedsEnvelopes;
use Support\Webhooks\Sending\Events\NeedsSent;

#[Dispatches(
    collecting: NeedsEnvelopes::class,
    sending: NeedsSent::class,
)]
#[Queues(collecting: 'webhooks.queues.collecting', sending: 'webhooks.queues.sending')]
#[Tries(3)]
interface Webhook extends Transport {}
