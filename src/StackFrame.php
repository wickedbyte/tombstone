<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

readonly class StackFrame implements \JsonSerializable
{
    public const TYPE_STATIC = '::';

    public const TYPE_OBJECT = '->';

    public const TYPE_FUNCTION = null;

    /**
     * Note: the args and object properties are not used, but we keep them for
     * full compatibility with the possible elements returned from debug_backtrace().
     * There are some cases where the args array is populated, even with the
     * ignore args flag set.
     *
     * @param array<int, mixed> $args
     */
    public function __construct(
        public string $file = '',
        public int $line = 0,
        public string $function = '',
        public string|null $class = null,
        public string|null $type = null,
        public array $args = [],
        public object|null $object = null,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'class' => $this->class,
            'type' => $this->type,
            'function' => $this->function,
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
