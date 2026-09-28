<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints\Status\Events;

use Support\Webhooks\Endpoints\Endpoint;

class Deactivated
{
    public readonly Endpoint $endpoint;

    public function __construct(Endpoint $endpoint)
    {
        $this->endpoint = $endpoint;
    }
}
