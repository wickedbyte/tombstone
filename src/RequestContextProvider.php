<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

interface RequestContextProvider
{
    public function context(): RequestContext;
}
