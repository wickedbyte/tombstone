<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\Handlers\PsrLoggerHandler;
use WickedByte\Tombstone\StackFrame;

#[CoversClass(PsrLoggerHandler::class)]
class PsrLoggerHandlerTest extends TestCase
{
    use StubsTombstoneActivation;

    #[Test]
    #[DataProvider('providesPsrLogLevels')]
    public function handlerRecordsTombstoneActivation(string $log_level): void
    {
        $tombstone = self::stubTombstoneActivation(caller: new StackFrame(
            function: __FUNCTION__,
            class: self::class,
            type: StackFrame::TYPE_OBJECT,
        ));

        self::assertSame(self::class . StackFrame::TYPE_OBJECT . __FUNCTION__, $tombstone->reference);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with($log_level, 'Tombstone Activated: ' . $tombstone->reference, [
                'tombstone' => $tombstone->toArray(),
            ]);

        $handler = new PsrLoggerHandler($logger, $log_level);
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);
    }

    public static function providesPsrLogLevels(): \Generator
    {
        foreach ((new ReflectionClass(LogLevel::class))->getConstants() as $level) {
            yield $level => [$level];
        }
    }

    #[Test]
    public function handlerValidatesLogLevel(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid PSR Log Level: foo');
        new PsrLoggerHandler($this->createMock(LoggerInterface::class), 'foo');
    }
}
