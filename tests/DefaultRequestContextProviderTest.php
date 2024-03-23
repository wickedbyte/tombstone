<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WickedByte\Tombstone\DefaultRequestContextProvider;
use WickedByte\Tombstone\RequestContext;

#[CoversClass(DefaultRequestContextProvider::class)]
class DefaultRequestContextProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('providesTestCases')]
    public function contextReturnsExpectedRequestContext(): void
    {
        $provider = new DefaultRequestContextProvider([]);
        self::assertEquals(new RequestContext(), $provider->context());
    }

    public static function providesTestCases(): \Generator
    {
        yield [new RequestContext(), []];

        $context = new RequestContext(
            'GET',
            'foo=bar',
            '/foo/bar',
            'example.com',
            'Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0',
            '192.168.0.255',
        );

        yield [$context, [
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/foo/bar',
            'HTTP_HOST' => 'example.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0',
            'HTTP_TRUE_CLIENT_IP' => '192.168.0.255',
        ]];

        yield [$context, [
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/foo/bar',
            'HTTP_HOST' => 'example.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0',
            'HTTP_X_FORWARDED_FOR' => '192.168.0.255',
        ]];

        yield [$context, [
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/foo/bar',
            'HTTP_HOST' => 'example.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0',
            'REMOTE_ADDR' => '192.168.0.255',
        ]];
    }
}
