<?php

declare(strict_types=1);

namespace Support\Webhooks\Topics\Events;

use Support\Webhooks\Topics\Topic;

class Deleted
{
    final public readonly Topic $topic;

    public function __construct(Topic $topic)
    {
        $this->topic = $topic;
    }
}
