<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Concerns;

trait AnnouncesToLog
{
    public function announceToLog(): bool
    {
        return $this->update(['updated_at' => now()->addSecond()]);
    }
}
