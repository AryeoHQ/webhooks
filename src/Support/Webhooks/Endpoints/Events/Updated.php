<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints\Events;

use Support\Webhooks\Endpoints\Endpoint;

class Updated
{
    final public readonly Endpoint $endpoint;

    public function __construct(Endpoint $endpoint)
    {
        $this->endpoint = $endpoint;
    }
}
