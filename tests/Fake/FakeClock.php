<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Fake;

use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;

final class FakeClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $now = '2026-01-01T00:00:00+00:00')
    {
        $this->now = new DateTimeImmutable($now);
    }

    #[Override]
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify('+' . $interval);
    }
}
