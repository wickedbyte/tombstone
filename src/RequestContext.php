<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

class RequestContext implements \JsonSerializable
{
    public function __construct(
        public readonly string|null $method = null,
        public readonly string|null $query = null,
        public readonly string|null $path = null,
        public readonly string|null $host = null,
        public readonly string|null $user_agent = null,
        public readonly string|null $ip_address = null,
        public readonly string $sapi = \PHP_SAPI,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'query' => $this->query,
            'path' => $this->path,
            'host' => $this->host,
            'user_agent' => $this->user_agent,
            'ip_address' => $this->ip_address,
            'sapi' => $this->sapi,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
