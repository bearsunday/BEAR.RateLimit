<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Fake;

use BEAR\RateLimit\Http\RemoteAddressInterface;
use Override;

final class FakeRemoteAddress implements RemoteAddressInterface
{
    public string|null $address = '127.0.0.1';

    #[Override]
    public function address(): string|null
    {
        return $this->address;
    }
}
