<?php

declare(strict_types=1);

use WickedByte\Tombstone\Graveyard;

if (!\function_exists('tombstone')) {
    /**
     * @param array<string, mixed> $extra
     */
    function tombstone(string $message = '', array $extra = []): void
    {
        Graveyard::tombstone($message, $extra);
    }
}
