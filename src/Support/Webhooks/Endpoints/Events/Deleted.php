<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints\Events;

use Support\Webhooks\Endpoints\Endpoint;

class Deleted
{
    final public readonly Endpoint $endpoint;

    public function __construct(Endpoint $endpoint)
    {
        $this->endpoint = $endpoint;
    }
}
