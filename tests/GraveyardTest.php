<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WickedByte\Tombstone\Graveyard;
use WickedByte\Tombstone\GraveyardConfiguration;
use WickedByte\Tombstone\Handlers\InMemoryRateLimitHandler;
use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneHandler;

#[CoversClass(Graveyard::class)]
final class GraveyardTest extends TestCase
{
    protected function setUp(): void
    {
        Graveyard::reset();
    }

    #[Test]
    public function configFallsBackToDefaultConfiguration(): void
    {
        self::assertEquals(new GraveyardConfiguration(), Graveyard::config());
    }

    #[Test]
    public function configCanSetTheConfiguration(): void
    {
        $config = new GraveyardConfiguration(true, 12, handlers: [
            new InMemoryRateLimitHandler(),
        ]);

        self::assertSame($config, Graveyard::config($config));
        self::assertSame($config, Graveyard::config());
    }

    public function resetResetsTheConfigurationState(): void
    {
        $config = new GraveyardConfiguration(true, 12, handlers: [
            new InMemoryRateLimitHandler(),
        ]);

        self::assertSame($config, Graveyard::config($config));
        Graveyard::reset();
        self::assertEquals(new GraveyardConfiguration(), Graveyard::config());
    }

    #[Test]
    public function tombstoneDispatchesTombstoneActivated(): void
    {
        $handler = $this->createMock(TombstoneHandler::class);
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(self::callback(static function (TombstoneActivated $tombstone): bool {
                self::assertSame('2024-01-01 Test Message', $tombstone->message);
                self::assertSame(self::class, $tombstone->caller->class);
                self::assertSame('tombstoneDispatchesTombstoneActivated', $tombstone->caller->function);
                self::assertSame(__FILE__, $tombstone->caller->file);
                self::assertSame(68, $tombstone->caller->line);
                return true;
            }));

        Graveyard::config(new GraveyardConfiguration(true, handlers: [$handler]));
        Graveyard::tombstone('2024-01-01 Test Message');
    }
}
