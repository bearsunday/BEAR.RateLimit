<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Http;

/**
 * The address of the peer that opened the connection (`REMOTE_ADDR`), or null when there is none.
 *
 * Forwarding headers such as X-Forwarded-For are not read: any client can forge them. Behind a
 * trusted proxy, bind an implementation that resolves the client past the proxy.
 */
interface RemoteAddressInterface
{
    public function address(): string|null;
}
