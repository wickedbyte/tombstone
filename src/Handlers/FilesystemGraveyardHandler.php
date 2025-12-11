<?php

declare(strict_types=1);

namespace WickedByte\Tombstone\Handlers;

use Psr\Log\LoggerInterface;
use WickedByte\Tombstone\TombstoneActivated;
use WickedByte\Tombstone\TombstoneException;
use WickedByte\Tombstone\TombstoneHandler;

class FilesystemGraveyardHandler implements TombstoneHandler
{
    protected final const ALLOWED_ERROR_LEVELS = [
        \E_USER_DEPRECATED,
        \E_USER_NOTICE,
        \E_USER_WARNING,
        \E_USER_ERROR, // kept for backwards compatibility, but not recommended, as it is deprecated in PHP 8.4
    ];

    public function __construct(
        protected readonly string $directory,
        protected readonly bool $stop_propagation = true,
        protected readonly int $permissions = 0777,
        protected readonly LoggerInterface|null $logger = null,
        protected readonly bool $throw_on_error = false,
        protected readonly int|null $error_level = null,
    ) {
        $directory !== '' || throw new \UnexpectedValueException('directory must not be empty');
        if ($error_level !== null && !\in_array($error_level, self::ALLOWED_ERROR_LEVELS, true)) {
            throw new \UnexpectedValueException('invalid php user error level: ' . $error_level);
        }
    }

    public function __invoke(TombstoneActivated $tombstone): void
    {
        try {
            $file_name = \sprintf('%s/%s.json', $this->directory, $tombstone->id);
            match (true) {
                \file_exists($file_name) => $tombstone->propagate = !$this->stop_propagation,
                !$this->checkDirectoryExists() => $this->fail('directory not created: ' . $this->directory),
                !$this->checkDirectoryPermissions() => $this->fail('directory not writable: ' . $this->directory),
                !$this->writeTombstoneFile($file_name, $tombstone) => $this->fail('file write failed: ' . $file_name),
                default => null, // happy path ends here.
            };
        } catch (\Exception $e) {
            $message = 'filesystem graveyard handler error: ' . $e->getMessage();
            $this->logger?->error($message, ['exception' => $e, 'tombstone' => $tombstone->toArray()]);
            $this->error_level && \trigger_error($message, $this->error_level);
            $this->throw_on_error && throw $e;
        }
    }

    private function fail(string $message): never
    {
        throw new TombstoneException($message);
    }

    /**
     * Check if the directory exists, and create it if it does not.
     * If the directory cannot be created, we check if it exists again, in order
     * to handle the edge case where a concurrent process created the directory
     * in between the first two function calls.
     *
     * Note: The $permissions parameter of `mkdir` is affected by the current
     * global umask setting, so we will leave it as is, and call `chmod` after
     * successfully creating the directory to ensure the correct permissions.
     */
    private function checkDirectoryExists(): bool
    {
        return \is_dir($this->directory)
            || (\mkdir($this->directory, recursive: true) && \chmod($this->directory, $this->permissions))
            || \is_dir($this->directory);
    }

    private function checkDirectoryPermissions(): bool
    {
        return \is_writable($this->directory);
    }

    private function writeTombstoneFile(string $file_name, TombstoneActivated $tombstone): bool
    {
        return (bool)\file_put_contents(
            $file_name,
            \json_encode($tombstone, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT),
        );
    }
}
