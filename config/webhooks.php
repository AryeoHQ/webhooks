<?php

declare(strict_types=1);

return [
    'queues' => [
        'collecting' => env('WEBHOOKS_QUEUE_COLLECTING'),
        'sending' => env('WEBHOOKS_QUEUE_SENDING'),
    ],

    'timeouts' => [
        'connect' => (int) env('WEBHOOKS_TIMEOUT_CONNECT', 5),
        'request' => (int) env('WEBHOOKS_TIMEOUT_REQUEST', 10),
    ],

    'subscriptions' => [
        'failures' => [
            // Disable a subscription after this many consecutive terminal delivery failures.
            // Set to 0 to never auto-disable.
            'threshold' => (int) env('WEBHOOKS_SUBSCRIPTION_FAILURE_THRESHOLD', 10),
        ],
    ],
];
