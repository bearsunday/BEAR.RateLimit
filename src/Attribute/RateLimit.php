<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class RateLimit
{
    /** @param string $interval PHP relative format without a sign, e.g. `'30 minutes'`. */
    public function __construct(
        public int $limit,
        public string $interval,
    ) {
    }
}
