<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\Handlers\PsrCacheHandler;
use WickedByte\Tombstone\TombstoneActivated;

#[CoversClass(PsrCacheHandler::class)]
class PsrCacheHandlerTest extends TestCase
{
    use StubsTombstoneActivation;

    #[Test]
    #[DataProvider('providesCacheMissTestCases')]
    public function handlerMayCacheTombstoneOnCacheMiss(
        TombstoneActivated $tombstone,
        bool $cache_event,
        TombstoneActivated|int $cached,
    ): void {
        $cache_item = $this->createMock(CacheItemInterface::class);
        $cache_item->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $cache_item->expects($this->once())
            ->method('set')
            ->with($cached)
            ->willReturnSelf();
        $cache_item->expects($this->once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturnSelf();

        $cache_item_pool = $this->createMock(CacheItemPoolInterface::class);
        $cache_item_pool->expects($this->once())
            ->method('getItem')
            ->with($tombstone->id)
            ->willReturn($cache_item);

        $handler = new PsrCacheHandler($cache_item_pool, 3600, cache_event: $cache_event);
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);
    }

    public static function providesCacheMissTestCases(): \Generator
    {
        $tombstone = self::stubTombstoneActivation();

        yield [$tombstone, false, 1];
        yield [$tombstone, true, $tombstone];
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function handlerMayChangeTombstonePropagationOnCacheHit(bool $stop_propagation): void
    {
        $tombstone = self::stubTombstoneActivation();

        $cache_item = $this->createMock(CacheItemInterface::class);
        $cache_item->expects($this->once())->method('isHit')->willReturn(true);
        $cache_item->expects($this->never())->method('set');
        $cache_item->expects($this->never())->method('expiresAfter');

        $cache_item_pool = $this->createMock(CacheItemPoolInterface::class);
        $cache_item_pool->expects($this->once())
            ->method('getItem')
            ->with($tombstone->id)
            ->willReturn($cache_item);

        $handler = new PsrCacheHandler($cache_item_pool, 3600, stop_propagation: $stop_propagation);
        $handler($tombstone);

        self::assertSame(!$stop_propagation, $tombstone->propagate);
    }

    #[Test]
    public function handlerValidatesNonNegativeTtl(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('TTL must be greater than or equal to zero');
        new PsrCacheHandler($this->createMock(CacheItemPoolInterface::class), -1);
    }
}
