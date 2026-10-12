<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Http;

use Override;

use function is_string;

/** @SuppressWarnings("PHPMD.Superglobals") Server variable adapter boundary. */
final readonly class ServerRemoteAddress implements RemoteAddressInterface
{
    /** @psalm-suppress TypeDoesNotContainType Psalm types REMOTE_ADDR as non-empty, but the SAPI can set it to ''. */
    #[Override]
    public function address(): string|null
    {
        $value = $_SERVER['REMOTE_ADDR'] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
