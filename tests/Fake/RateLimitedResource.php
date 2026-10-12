<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Fake;

use BEAR\RateLimit\Attribute\RateLimit;
use BEAR\Resource\ResourceObject;

class RateLimitedResource extends ResourceObject
{
    public int $reached = 0;

    #[RateLimit(limit: 2, interval: '1 minute')]
    public function onGet(): static
    {
        $this->reached++;

        return $this;
    }
}
