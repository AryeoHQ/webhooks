<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Webhooks\Subscriptions\Subscription;

final class Deactivate extends Trigger
{
    #[Target]
    protected readonly Subscription $subscription;

    public function handle(): void {}
}
