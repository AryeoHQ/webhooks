<?php

declare(strict_types=1);

namespace Support\Webhooks\Endpoints\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Webhooks\Endpoints\Endpoint;

final class Disable extends Trigger
{
    #[Target]
    protected readonly Endpoint $endpoint;

    public function handle(): void {}
}
