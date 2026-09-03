<?php

declare(strict_types=1);

return [
    'queues' => [
        'collecting' => env('WEBHOOKS_COLLECTING_QUEUE'),
        'sending' => env('WEBHOOKS_SENDING_QUEUE'),
    ],
];
