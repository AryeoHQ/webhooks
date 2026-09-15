<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Status;

use Support\Database\Eloquent\StateMachines\Attributes\Events\Events;
use Support\Database\Eloquent\StateMachines\Attributes\Transitions\Transition;
use Support\Database\Eloquent\StateMachines\Contracts\StateMachineable;
use Support\Database\Eloquent\StateMachines\Provides\ManagesState;
use Support\Webhooks\Subscriptions\Status\Events\Activated;
use Support\Webhooks\Subscriptions\Status\Events\Activating;
use Support\Webhooks\Subscriptions\Status\Events\Deactivated;
use Support\Webhooks\Subscriptions\Status\Events\Deactivating;
use Support\Webhooks\Subscriptions\Status\Events\Disabled;
use Support\Webhooks\Subscriptions\Status\Events\Disabling;
use Support\Webhooks\Subscriptions\Status\Triggers\Activate;
use Support\Webhooks\Subscriptions\Status\Triggers\Deactivate;
use Support\Webhooks\Subscriptions\Status\Triggers\Disable;

/**
 * @method \Support\Webhooks\Subscriptions\Status\Triggers\Deactivate deactivate()
 * @method \Support\Webhooks\Subscriptions\Status\Triggers\Disable disable()
 * @method \Support\Webhooks\Subscriptions\Status\Triggers\Activate activate()
 */
enum Status: string implements StateMachineable
{
    use ManagesState;

    #[Events(before: Activating::class, after: Activated::class)]
    #[Transition(to: self::Inactive, using: Deactivate::class)]
    #[Transition(to: self::Disabled, using: Disable::class)]
    case Active = 'active';

    #[Events(before: Deactivating::class, after: Deactivated::class)]
    #[Transition(to: self::Active, using: Activate::class)]
    case Inactive = 'inactive';

    #[Events(before: Disabling::class, after: Disabled::class)]
    #[Transition(to: self::Active, using: Activate::class)]
    case Disabled = 'disabled';
}
