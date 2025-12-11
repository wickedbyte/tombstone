<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\Handlers\PhpErrorHandler;
use WickedByte\Tombstone\StackFrame;

#[CoversClass(PhpErrorHandler::class)]
final class PhpErrorHandlerTest extends TestCase
{
    use StubsTombstoneActivation;

    #[Test]
    #[WithoutErrorHandler]
    #[DataProvider('providesUserErrorLevels')]
    public function handlerTriggersPhpError(int $error_level): void
    {
        $tombstone = self::stubTombstoneActivation('test-stub-message', new StackFrame(
            file: __FILE__,
            line: 42,
            function: 'someOldClassMethod',
            class: self::class,
            type: StackFrame::TYPE_OBJECT,
        ));

        $previous_handler = \set_error_handler(static function (
            int $level,
            string $message,
            string $file,
            int $line,
        ): never {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }, \E_USER_DEPRECATED | \E_USER_NOTICE | \E_USER_WARNING | \E_USER_ERROR);

        try {
            $handler = new PhpErrorHandler($error_level);
            $handler($tombstone);
            self::fail('Expected error-as-exception to be thrown');
        } catch (\ErrorException $e) {
            self::assertSame(\vsprintf('Tombstone Activated: %s in %s on line %s - %s', [
                $tombstone->reference,
                __FILE__,
                42,
                'test-stub-message',
            ]), $e->getMessage());
            self::assertSame($error_level, $e->getSeverity());
            self::assertTrue($tombstone->propagate);
        } finally {
            \set_error_handler($previous_handler);
        }
    }

    public static function providesUserErrorLevels(): \Generator
    {
        yield [\E_USER_DEPRECATED];
        yield [\E_USER_NOTICE];
        yield [\E_USER_WARNING];
    }

    #[Test]
    #[DataProvider('providesNonUserErrorLevels')]
    public function handlerValidatesErrorLevel(int $error_level): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('invalid php user error level: ' . $error_level);
        new PhpErrorHandler($error_level);
    }

    public static function providesNonUserErrorLevels(): \Generator
    {
        yield [\E_ERROR];
        yield [\E_WARNING];
        yield [\E_PARSE];
        yield [\E_NOTICE];
        yield [\E_CORE_ERROR];
        yield [\E_CORE_WARNING];
        yield [\E_COMPILE_ERROR];
        yield [\E_COMPILE_WARNING];
        yield [\E_RECOVERABLE_ERROR];
        yield [\E_DEPRECATED];
        yield [\E_ALL];
        yield [0];
        yield [-1];
        yield [\PHP_INT_MAX];
        yield [\E_USER_DEPRECATED | \E_USER_NOTICE | \E_USER_WARNING | \E_USER_ERROR];
        yield [\E_USER_DEPRECATED | \E_USER_NOTICE | \E_USER_WARNING | \E_USER_ERROR | \E_WARNING];
    }
}
