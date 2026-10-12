<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Throttle;

use Override;
use Psr\Clock\ClockInterface;

/** Counts live only in this process: for tests and single-process demos, not a production store. */
final class InMemoryThrottle implements ThrottleInterface
{
    /** @var array<string, array{attempts: int, expiresAt: int}> */
    private array $windows = [];

    public function __construct(private readonly ClockInterface $clock)
    {
    }

    #[Override]
    public function isExceeded(string $key, int $limit): bool
    {
        $window = $this->windows[$key] ?? null;

        return $window !== null
            && $window['expiresAt'] > $this->clock->now()->getTimestamp()
            && $window['attempts'] >= $limit;
    }

    #[Override]
    public function countUp(string $key, string $interval): void
    {
        $now = $this->clock->now();
        $window = $this->windows[$key] ?? null;
        if ($window !== null && $window['expiresAt'] > $now->getTimestamp()) {
            $this->windows[$key]['attempts']++;

            return;
        }

        $this->windows[$key] = [
            'attempts' => 1,
            'expiresAt' => $now->modify('+' . $interval)->getTimestamp(),
        ];
    }
}
