<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use BEAR\RateLimit\Http\ServerRemoteAddress;
use PHPUnit\Framework\TestCase;

final class ServerRemoteAddressTest extends TestCase
{
    /** @var array<mixed> */
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testReadsRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';

        $this->assertSame('192.0.2.1', (new ServerRemoteAddress())->address());
    }

    public function testReturnsNullWhenRemoteAddrIsAbsent(): void
    {
        unset($_SERVER['REMOTE_ADDR']);

        $this->assertNull((new ServerRemoteAddress())->address());
    }

    public function testReturnsNullWhenRemoteAddrIsEmpty(): void
    {
        $_SERVER['REMOTE_ADDR'] = '';

        $this->assertNull((new ServerRemoteAddress())->address());
    }
}
