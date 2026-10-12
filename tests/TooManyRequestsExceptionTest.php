<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use BEAR\RateLimit\Exception\TooManyRequestsException;
use BEAR\Resource\Exception\BadRequestException;
use PHPUnit\Framework\TestCase;

final class TooManyRequestsExceptionTest extends TestCase
{
    public function testIsA429BadRequest(): void
    {
        $exception = new TooManyRequestsException();

        $this->assertSame(429, $exception->getCode());
        $this->assertInstanceOf(BadRequestException::class, $exception);
    }
}
