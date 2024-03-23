<?php

declare(strict_types=1);

namespace WickedByte\Tombstone\Handlers;

use Psr\Cache\CacheItemPoolInterface;
use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneHandler;

class PsrCacheHandler implements TombstoneHandler
{
    public function __construct(
        protected readonly CacheItemPoolInterface $cache,
        protected readonly int $ttl = 86400,
        protected readonly bool $cache_event = false,
        protected readonly bool $stop_propagation = true,
    ) {
        $ttl >= 0 || throw new \UnexpectedValueException('TTL must be greater than or equal to zero');
    }

    public function __invoke(TombstoneActivated $tombstone): void
    {
        $item = $this->cache->getItem($tombstone->id);
        if ($item->isHit()) {
            $tombstone->propagate = !$this->stop_propagation;
        } else {
            $this->cache->save($item->set($this->cache_event ? $tombstone : 1)->expiresAfter($this->ttl));
        }
    }
}
