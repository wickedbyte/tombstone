<?php

declare(strict_types=1);

namespace WickedByte\Tombstone\Handlers;

use Psr\SimpleCache\CacheInterface;
use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneHandler;

class PsrSimpleCacheHandler implements TombstoneHandler
{
    public function __construct(
        protected readonly CacheInterface $cache,
        protected readonly int $ttl = 86400,
        protected readonly bool $cache_event = false,
        protected readonly bool $stop_propagation = true,
    ) {
        $ttl >= 0 || throw new \UnexpectedValueException('TTL must be greater than or equal to zero');
    }

    public function __invoke(TombstoneActivated $tombstone): void
    {
        if ($this->cache->get($tombstone->id)) {
            $tombstone->propagate = !$this->stop_propagation;
        } else {
            $this->cache->set($tombstone->id, $this->cache_event ? $tombstone : 1, $this->ttl);
        }
    }
}
