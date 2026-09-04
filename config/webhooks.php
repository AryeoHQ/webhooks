<?php

declare(strict_types=1);

return [
    'queues' => [
        'collecting' => env('WEBHOOKS_COLLECTING_QUEUE'),
        'sending' => env('WEBHOOKS_SENDING_QUEUE'),
    ],

    'failure' => [
        // Disable a subscription after this many consecutive terminal delivery failures.
        // Set to 0 to never auto-disable.
        'threshold' => (int) env('WEBHOOKS_FAILURE_THRESHOLD', 10),
    ],
];
