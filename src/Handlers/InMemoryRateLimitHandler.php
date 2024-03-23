<?php

declare(strict_types=1);

namespace WickedByte\Tombstone\Handlers;

use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneHandler;

class InMemoryRateLimitHandler implements TombstoneHandler
{
    /**
     * @var array<string, true>
     */
    protected array $tombstones = [];

    public function __invoke(TombstoneActivated $tombstone): void
    {
        $tombstone->propagate = false;
        $this->tombstones[$tombstone->id] ??= $tombstone->propagate = true;
    }
}
