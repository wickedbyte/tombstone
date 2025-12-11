<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

class DefaultRequestContextProvider implements RequestContextProvider
{
    /**
     * @var array<string, mixed>
     */
    private readonly array $server;

    /**
     * @param array<string, mixed>|null $server
     */
    public function __construct(array|null $server = null)
    {
        /** @phpstan-ignore assign.propertyType (allow default superglobal without breaking static analysis rules) */
        $this->server = $server ?? $_SERVER;
    }

    public function context(): RequestContext
    {
        return new RequestContext(
            self::parse($this->server['REQUEST_METHOD'] ?? null),
            self::parse($this->server['QUERY_STRING'] ?? null),
            self::parse($this->server['REQUEST_URI'] ?? null),
            self::parse($this->server['HTTP_HOST'] ?? null),
            self::parse($this->server['HTTP_USER_AGENT'] ?? null),
            self::parse($this->server['HTTP_TRUE_CLIENT_IP']
                ?? $this->server['HTTP_X_FORWARDED_FOR']
                ?? $this->server['REMOTE_ADDR']
                ?? null,),
        );
    }

    private static function parse(mixed $value): string|null
    {
        return match (true) {
            $value === '' => null,
            \is_string($value), $value === null => $value,
            \is_scalar($value) => (string)$value,
            default => throw new \InvalidArgumentException('Invalid value for context parameter'),
        };
    }
}
