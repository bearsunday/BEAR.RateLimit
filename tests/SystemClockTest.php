<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use PHPUnit\Framework\TestCase;

use function time;

final class SystemClockTest extends TestCase
{
    public function testNowIsTheCurrentTimeInUtc(): void
    {
        $now = (new SystemClock())->now();

        $this->assertSame('UTC', $now->getTimezone()->getName());
        $this->assertEqualsWithDelta(time(), $now->getTimestamp(), 2);
    }
}
