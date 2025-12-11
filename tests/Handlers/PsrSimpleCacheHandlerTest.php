<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\Handlers\PsrSimpleCacheHandler;
use WickedByte\Tombstone\TombstoneActivated;

#[CoversClass(PsrSimpleCacheHandler::class)]
final class PsrSimpleCacheHandlerTest extends TestCase
{
    use StubsTombstoneActivation;

    #[Test]
    #[DataProvider('providesCacheMissTestCases')]
    public function handlerMayCacheTombstoneOnCacheMiss(
        TombstoneActivated $tombstone,
        bool $cache_event,
        TombstoneActivated|int $cached,
    ): void {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($tombstone->id)
            ->willReturn(null);
        $cache->expects($this->once())
            ->method('set')
            ->with($tombstone->id, $cached, 3600);

        $handler = new PsrSimpleCacheHandler($cache, 3600, cache_event: $cache_event);
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
    #[DataProvider('providesCacheHitTestCases')]
    public function handlerMayChangeTombstonePropagationOnCacheHit(
        TombstoneActivated $tombstone,
        bool $stop_propagation,
        TombstoneActivated|int $cached,
    ): void {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($tombstone->id)
            ->willReturn($cached);
        $cache->expects($this->never())->method('set');

        $handler = new PsrSimpleCacheHandler($cache, 3600, stop_propagation: $stop_propagation);
        $handler($tombstone);

        self::assertSame(!$stop_propagation, $tombstone->propagate);
    }

    public static function providesCacheHitTestCases(): \Generator
    {
        $tombstone = self::stubTombstoneActivation();

        yield [$tombstone, false, 1];
        yield [$tombstone, true, 1];
        yield [$tombstone, false, $tombstone];
        yield [$tombstone, true, $tombstone];
    }

    #[Test]
    public function handlerValidatesNonNegativeTtl(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('TTL must be greater than or equal to zero');
        new PsrSimpleCacheHandler($this->createStub(CacheInterface::class), -1);
    }
}
