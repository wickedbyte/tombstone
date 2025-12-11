<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\Handlers\PsrEventDispatcherHandler;

#[CoversClass(PsrEventDispatcherHandler::class)]
final class PsrEventDispatcherHandlerTest extends TestCase
{
    use StubsTombstoneActivation;

    #[Test]
    public function handlerDispatchesTombstoneActivation(): void
    {
        $tombstone = self::stubTombstoneActivation();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($tombstone);

        $handler = new PsrEventDispatcherHandler($dispatcher);
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);
    }
}
