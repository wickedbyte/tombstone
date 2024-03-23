<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Fixtures;

use WickedByte\Tombstone\RequestContext;
use WickedByte\Tombstone\StackFrame;
use WickedByte\Tombstone\TombstoneActivated;

trait StubsTombstoneActivation
{
    /**
     * @param array<string, mixed> $extra
     * @param array<int, StackFrame> $trace
     */
    public static function stubTombstoneActivation(
        string $message = 'test-stub',
        StackFrame $caller = new StackFrame(),
        array $trace = [],
        array $extra = [],
        RequestContext $context = new RequestContext(),
        \DateTimeImmutable $timestamp = new \DateTimeImmutable(),
        bool $propagate = true,
    ): TombstoneActivated {
        return new TombstoneActivated($message, $caller, $trace, $extra, $context, $timestamp, $propagate);
    }
}
