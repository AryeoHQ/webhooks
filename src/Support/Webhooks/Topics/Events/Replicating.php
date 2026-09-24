<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Events;

use Support\Webhooks\Topics\Topic;

class Replicating
{
    final public readonly Topic $topic;

    public function __construct(Topic $topic)
    {
        $this->topic = $topic;
    }
}
