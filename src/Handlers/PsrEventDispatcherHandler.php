<?php

declare(strict_types=1);

namespace WickedByte\Tombstone\Handlers;

use Psr\EventDispatcher\EventDispatcherInterface;
use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneHandler;

class PsrEventDispatcherHandler implements TombstoneHandler
{
    public function __construct(protected readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function __invoke(TombstoneActivated $tombstone): void
    {
        $this->dispatcher->dispatch($tombstone);
    }
}
