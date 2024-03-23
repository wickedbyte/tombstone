<?php

declare(strict_types=1);

namespace WickedByte\Tombstone\Handlers;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneHandler;

class PsrLoggerHandler implements TombstoneHandler
{
    public final const ALLOWED_LOG_LEVELS = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY,
    ];

    private const MESSAGE_TEMPLATE = 'Tombstone Activated: %s';

    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly string $level = LogLevel::WARNING,
    ) {
        if (!\in_array($level, self::ALLOWED_LOG_LEVELS, true)) {
            throw new \UnexpectedValueException('Invalid PSR Log Level: ' . $level);
        }
    }

    public function __invoke(TombstoneActivated $tombstone): void
    {
        $this->logger->log($this->level, \sprintf(self::MESSAGE_TEMPLATE, $tombstone->reference), [
            'tombstone' => $tombstone->toArray(),
        ]);
    }
}
