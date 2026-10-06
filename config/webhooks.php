<?php

declare(strict_types=1);

return [
    'source' => env('WEBHOOKS_SOURCE'),

    'queues' => [
        'collecting' => env('WEBHOOKS_QUEUE_COLLECTING'),
        'sending' => env('WEBHOOKS_QUEUE_SENDING'),
    ],

    'timeouts' => [
        'connect' => (int) env('WEBHOOKS_TIMEOUT_CONNECT', 5),
        'request' => (int) env('WEBHOOKS_TIMEOUT_REQUEST', 10),
    ],

    'endpoints' => [
        'failures' => [
            // Disable an endpoint after this many consecutive terminal delivery failures.
            // Set to 0 to never auto-disable.
            'threshold' => (int) env('WEBHOOKS_ENDPOINT_FAILURE_THRESHOLD', 10),
        ],
    ],
];
