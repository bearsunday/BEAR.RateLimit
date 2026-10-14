<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Throttle;

final readonly class SqliteDsn
{
    public function __construct(public string $dsn)
    {
    }
}
