<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

use Psr\Log\LoggerInterface;
use WickedByte\Tombstone\Handlers\InMemoryRateLimitHandler;
use WickedByte\Tombstone\Handlers\PhpErrorHandler;

readonly class GraveyardConfiguration
{
    /**
     * @param array<array-key, TombstoneHandler> $handlers
     */
    public function __construct(
        public bool $rethrow_exceptions = false,
        public int $trace_depth = 10,
        public LoggerInterface|null $logger = null,
        public RequestContextProvider $context_provider = new DefaultRequestContextProvider(),
        public array $handlers = [new InMemoryRateLimitHandler(), new PhpErrorHandler()],
    ) {
        $trace_depth > 0 || throw new \UnexpectedValueException('Trace depth must be greater than zero');
        foreach ($handlers as $handler) {
            $handler instanceof TombstoneHandler || throw new \InvalidArgumentException('Handlers must implement TombstoneHandler');
        }
    }
}
