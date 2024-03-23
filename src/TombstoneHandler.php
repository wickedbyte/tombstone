<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

interface TombstoneHandler
{
    public function __invoke(TombstoneActivated $tombstone): void;
}
