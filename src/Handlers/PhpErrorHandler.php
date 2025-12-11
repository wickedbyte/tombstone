<?php

declare(strict_types=1);

namespace WickedByte\Tombstone\Handlers;

use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneHandler;

class PhpErrorHandler implements TombstoneHandler
{
    protected final const ALLOWED_ERROR_LEVELS = [
        \E_USER_DEPRECATED,
        \E_USER_NOTICE,
        \E_USER_WARNING,
        \E_USER_ERROR, // keep for backwards compatibility, but not recommended, as it is deprecated in PHP 8.4
    ];

    public function __construct(protected readonly int $error_level = \E_USER_DEPRECATED)
    {
        if (!\in_array($error_level, self::ALLOWED_ERROR_LEVELS, true)) {
            throw new \UnexpectedValueException('invalid php user error level: ' . $error_level);
        }
    }

    public function __invoke(TombstoneActivated $tombstone): void
    {
        \trigger_error(\vsprintf('Tombstone Activated: %s in %s on line %d - %s', [
            $tombstone->reference,
            $tombstone->caller->file ?: 'Undefined',
            $tombstone->caller->line,
            \trim($tombstone->message),
        ]), $this->error_level);
    }
}
