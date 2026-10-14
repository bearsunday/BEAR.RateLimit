<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Throttle;

/**
 * Fixed windows: countUp() opens a window of $interval when none is live and never extends a live one.
 * A window is gone at the instant it expires.
 */
interface ThrottleInterface
{
    /** True when the live window for $key holds $limit attempts or more. */
    public function isExceeded(string $key, int $limit): bool;

    /** @param string $interval PHP relative format without a sign, applied as `'+' . $interval`. */
    public function countUp(string $key, string $interval): void;
}
