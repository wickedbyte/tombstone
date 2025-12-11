<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use WickedByte\Tombstone\DefaultRequestContextProvider;
use WickedByte\Tombstone\GraveyardConfiguration;
use WickedByte\Tombstone\Handlers\InMemoryRateLimitHandler;
use WickedByte\Tombstone\Handlers\PhpErrorHandler;
use WickedByte\Tombstone\Handlers\PsrEventDispatcherHandler;
use WickedByte\Tombstone\Handlers\PsrLoggerHandler;
use WickedByte\Tombstone\RequestContextProvider;

#[CoversClass(GraveyardConfiguration::class)]
final class GraveyardConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
    }

    #[Test]
    public function configurationProvidesExpectedDefaultValues(): void
    {
        $config = new GraveyardConfiguration();

        self::assertEquals([
            new InMemoryRateLimitHandler(),
            new PhpErrorHandler(),
        ], $config->handlers);

        self::assertFalse($config->rethrow_exceptions);
        self::assertSame(10, $config->trace_depth);
        self::assertNull($config->logger);
        self::assertEquals(new DefaultRequestContextProvider(), $config->context_provider);
    }

    #[Test]
    public function configurationProvidesExpectedValues(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $dispatcher = self::createStub(EventDispatcherInterface::class);
        $context_provider = self::createStub(RequestContextProvider::class);

        $config = new GraveyardConfiguration(true, 12, $logger, $context_provider, [
            new InMemoryRateLimitHandler(),
            new PsrLoggerHandler($logger),
            new PsrEventDispatcherHandler($dispatcher),
        ]);

        self::assertEquals([
            new InMemoryRateLimitHandler(),
            new PsrLoggerHandler($logger),
            new PsrEventDispatcherHandler($dispatcher),
        ], $config->handlers);

        self::assertTrue($config->rethrow_exceptions);
        self::assertSame(12, $config->trace_depth);
        self::assertSame($logger, $config->logger);
        self::assertSame($context_provider, $config->context_provider);
    }

    #[Test]
    public function handlersAreValidatedAsTombstoneHandlers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        /** @phpstan-ignore-next-line intentional defect for testing */
        new GraveyardConfiguration(true, 12, handlers: [
            new InMemoryRateLimitHandler(),
            new PhpErrorHandler(),
            new \stdClass(),
        ]);
    }
}
