<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\Handlers\InMemoryRateLimitHandler;
use WickedByte\Tombstone\StackFrame;

#[CoversClass(InMemoryRateLimitHandler::class)]
class InMemoryRateLimitHandlerTest extends TestCase
{
    use StubsTombstoneActivation;

    #[Test]
    public function handlerAllowsPropagationOnce(): void
    {
        $handler = new InMemoryRateLimitHandler();
        $caller = new StackFrame();

        $tombstone = self::stubTombstoneActivation('2024-01-01', $caller);
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);

        $tombstone = self::stubTombstoneActivation('2024-01-01', $caller);
        $handler($tombstone);

        self::assertFalse($tombstone->propagate);

        $tombstone = self::stubTombstoneActivation('different message', $caller);
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);

        $tombstone = self::stubTombstoneActivation('different message', $caller);
        $handler($tombstone);

        self::assertFalse($tombstone->propagate);

        $tombstone = self::stubTombstoneActivation('2024-01-01', new StackFrame(class: 'different_class'));
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);

        $tombstone = self::stubTombstoneActivation('2024-01-01', new StackFrame(class: 'different_class'));
        $handler($tombstone);

        self::assertFalse($tombstone->propagate);
    }
}
