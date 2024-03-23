<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

class DefaultRequestContextProvider implements RequestContextProvider
{
    /**
     * @var array<string, string>
     */
    private readonly array $server;

    /**
     * @param array<string, string>|null $server
     */
    public function __construct(array|null $server = null)
    {
        $this->server = $server ?? $_SERVER;
    }

    public function context(): RequestContext
    {
        return new RequestContext(
            $this->server['REQUEST_METHOD'] ?? null,
            $this->server['QUERY_STRING'] ?? null,
            $this->server['REQUEST_URI'] ?? null,
            $this->server['HTTP_HOST'] ?? null,
            $this->server['HTTP_USER_AGENT'] ?? null,
            $this->server['HTTP_TRUE_CLIENT_IP'] ?? $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? null,
        );
    }
}
