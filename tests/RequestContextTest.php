<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WickedByte\Tombstone\RequestContext;

#[CoversClass(RequestContext::class)]
final class RequestContextTest extends TestCase
{
    #[Test]
    public function toArrayHasExpectedRepresentation(): void
    {
        $context = new RequestContext(
            'method_value',
            'query_value',
            'path_value',
            'host_value',
            'user_agent_value',
            'ip_address_value',
        );

        self::assertSame([
            'method' => 'method_value',
            'query' => 'query_value',
            'path' => 'path_value',
            'host' => 'host_value',
            'user_agent' => 'user_agent_value',
            'ip_address' => 'ip_address_value',
            'sapi' => \PHP_SAPI,
        ], $context->toArray());

        self::assertSame([
            'method' => 'method_value',
            'query' => 'query_value',
            'path' => 'path_value',
            'host' => 'host_value',
            'user_agent' => 'user_agent_value',
            'ip_address' => 'ip_address_value',
            'sapi' => \PHP_SAPI,
        ], $context->jsonSerialize());
    }

    #[Test]
    public function toArrayHasExpectedRepresentationNullCase(): void
    {
        self::assertSame([
            'method' => null,
            'query' => null,
            'path' => null,
            'host' => null,
            'user_agent' => null,
            'ip_address' => null,
            'sapi' => \PHP_SAPI,
        ], (new RequestContext())->toArray());
    }
}
