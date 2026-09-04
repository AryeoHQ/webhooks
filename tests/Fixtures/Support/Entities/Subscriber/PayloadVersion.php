<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Subscriber;

use Support\Events\Log\Logs\Data\Version\Contracts\Version;

enum PayloadVersion: string implements Version
{
    case V1 = 'v1';
}
