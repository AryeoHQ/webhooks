<?php

declare(strict_types=1);

namespace Support\Webhooks\Subscriptions\Status;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Status::class)]
final class StatusTest extends TestCase
{
    #[Test]
    public function active_transitions(): void
    {
        Status::Active->assertDefinesTransitions(Status::Inactive, Status::Disabled);
    }

    #[Test]
    public function inactive_transitions(): void
    {
        Status::Inactive->assertDefinesTransitions(Status::Active);
    }

    #[Test]
    public function disabled_transitions(): void
    {
        Status::Disabled->assertDefinesTransitions(Status::Active);
    }
}
