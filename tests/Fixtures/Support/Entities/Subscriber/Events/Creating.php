<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Subscriber\Events;

use Tests\Fixtures\Support\Entities\Subscriber\Subscriber;

final class Creating
{
    public Subscriber $subscriber;

    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }
}
