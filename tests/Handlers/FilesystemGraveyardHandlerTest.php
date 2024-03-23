<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\Handlers\FilesystemGraveyardHandler;
use WickedByte\Tombstone\StackFrame;
use WickedByte\Tombstone\TombstoneException;

#[CoversClass(FilesystemGraveyardHandler::class)]
class FilesystemGraveyardHandlerTest extends TestCase
{
    use StubsTombstoneActivation;

    private const GRAVEYARD = __DIR__ . '/graveyard';

    protected function tearDown(): void
    {
        if (\is_dir(self::GRAVEYARD)) {
            foreach (\glob(self::GRAVEYARD . '/*') ?: [] as $file) {
                \unlink($file);
            }

            \rmdir(self::GRAVEYARD);
        }
    }

    #[Test]
    public function handlerValidatesDirectoryStringIsNonEmpty(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('directory must not be empty');
        new FilesystemGraveyardHandler('');
    }

    #[Test]
    #[DataProvider('providesNonUserErrorLevels')]
    public function handlerValidatesErrorLevel(int $error_level): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('invalid php user error level: ' . $error_level);
        new FilesystemGraveyardHandler(self::GRAVEYARD, error_level: $error_level);
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
        yield [\E_STRICT];
        yield [\E_RECOVERABLE_ERROR];
        yield [\E_DEPRECATED];
        yield [\E_ALL];
        yield [0];
        yield [-1];
        yield [\PHP_INT_MAX];
        yield [\E_USER_DEPRECATED | \E_USER_NOTICE | \E_USER_WARNING | \E_USER_ERROR];
        yield [\E_USER_DEPRECATED | \E_USER_NOTICE | \E_USER_WARNING | \E_USER_ERROR | \E_WARNING];
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function handlerMayChangeTombstonePropagationWhenFileExists(bool $stop_propagation): void
    {
        $tombstone = self::stubTombstoneActivation();
        $file_name = \sprintf('%s/%s.json', self::GRAVEYARD, $tombstone->id);

        \is_dir(self::GRAVEYARD) || \mkdir(self::GRAVEYARD);
        self::assertDirectoryExists(self::GRAVEYARD);

        \file_put_contents($file_name, \random_bytes(256));
        $hash = \hash_file('xxh3', $file_name);

        $handler = new FilesystemGraveyardHandler(self::GRAVEYARD, stop_propagation: $stop_propagation);
        $handler($tombstone);

        self::assertSame(!$stop_propagation, $tombstone->propagate);
        self::assertSame($hash, \hash_file('xxh3', $file_name));
    }

    #[Test]
    public function handlerRecordsActivationWhenDirectoryExists(): void
    {
        $tombstone = self::stubTombstoneActivation();
        $file_name = \sprintf('%s/%s.json', self::GRAVEYARD, $tombstone->id);

        \is_dir(self::GRAVEYARD) || \mkdir(self::GRAVEYARD);
        self::assertDirectoryExists(self::GRAVEYARD);
        self::assertFileDoesNotExist($file_name);

        $handler = new FilesystemGraveyardHandler(self::GRAVEYARD);
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);
        self::assertDirectoryExists(self::GRAVEYARD);
        self::assertFileExists($file_name);
        self::assertJsonStringEqualsJsonFile($file_name, \json_encode($tombstone->toArray(), \JSON_THROW_ON_ERROR));
    }

    #[Test]
    #[TestWith([0o0777])]
    #[TestWith([0o1777])]
    #[TestWith([0o0755])]
    public function handlerCreatesDirectoryWhenNotExists(int $permissions): void
    {
        $tombstone = self::stubTombstoneActivation();
        $file_name = \sprintf('%s/%s.json', self::GRAVEYARD, $tombstone->id);

        self::assertDirectoryDoesNotExist(self::GRAVEYARD);
        self::assertFileDoesNotExist($file_name);

        $handler = new FilesystemGraveyardHandler(self::GRAVEYARD, permissions: $permissions);
        $handler($tombstone);

        self::assertTrue($tombstone->propagate);
        self::assertDirectoryExists(self::GRAVEYARD);

        self::assertSame($permissions, \fileperms(self::GRAVEYARD) & 0o1777);
        self::assertFileExists($file_name);
        self::assertJsonStringEqualsJsonFile($file_name, \json_encode($tombstone->toArray(), \JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function handlerFailsWhenDirectoryNotWritable(): void
    {
        $tombstone = self::stubTombstoneActivation();

        \is_dir(self::GRAVEYARD) || \mkdir(self::GRAVEYARD);
        \chmod(self::GRAVEYARD, 0o0555);
        self::assertDirectoryIsNotWritable(self::GRAVEYARD);

        $handler = new FilesystemGraveyardHandler(self::GRAVEYARD);
        $handler($tombstone);
    }

    #[Test]
    public function handlerCanRethrowExceptions(): void
    {
        $tombstone = self::stubTombstoneActivation();

        \is_dir(self::GRAVEYARD) || \mkdir(self::GRAVEYARD);
        \chmod(self::GRAVEYARD, 0o0555);
        self::assertDirectoryIsNotWritable(self::GRAVEYARD);

        $handler = new FilesystemGraveyardHandler(self::GRAVEYARD, throw_on_error: true);

        $this->expectExceptionMessage(TombstoneException::class);
        $this->expectExceptionMessage('directory not writable: ' . self::GRAVEYARD);
        $handler($tombstone);
    }

    #[Test]
    public function handlerCanLogExceptions(): void
    {
        $tombstone = self::stubTombstoneActivation();

        \is_dir(self::GRAVEYARD) || \mkdir(self::GRAVEYARD);
        \chmod(self::GRAVEYARD, 0o0555);
        self::assertDirectoryIsNotWritable(self::GRAVEYARD);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'filesystem graveyard handler error: directory not writable: ' . self::GRAVEYARD,
            self::callback(static fn(array $arg): bool => ($arg['exception'] ?? null) instanceof TombstoneException
                && $arg['exception']->getMessage() === 'directory not writable: ' . self::GRAVEYARD
                && ($arg['tombstone'] ?? null) === $tombstone->toArray()),
        );

        $handler = new FilesystemGraveyardHandler(self::GRAVEYARD, logger: $logger);
        $handler($tombstone);
    }

    #[Test]
    #[WithoutErrorHandler]
    #[DataProvider('providesUserErrorLevels')]
    public function handlerCanTriggerPhpError(int $error_level): void
    {
        $tombstone = self::stubTombstoneActivation('test-stub-message', new StackFrame(
            file: __FILE__,
            line: 42,
            function: 'someOldClassMethod',
            class: self::class,
            type: StackFrame::TYPE_OBJECT,
        ));

        $previous_handler = \set_error_handler(static function (int $level, string $message, string $file, int $line): void {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }, \E_USER_DEPRECATED | \E_USER_NOTICE | \E_USER_WARNING | \E_USER_ERROR);

        try {
            \is_dir(self::GRAVEYARD) || \mkdir(self::GRAVEYARD);
            \chmod(self::GRAVEYARD, 0o0555);
            self::assertDirectoryIsNotWritable(self::GRAVEYARD);

            $handler = new FilesystemGraveyardHandler(self::GRAVEYARD, error_level: $error_level);
            $handler($tombstone);
            self::fail('Expected error-as-exception to be thrown');
        } catch (\ErrorException $e) {
            self::assertSame('filesystem graveyard handler error: directory not writable: ' . self::GRAVEYARD, $e->getMessage());
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
        yield [\E_USER_ERROR];
    }
}
