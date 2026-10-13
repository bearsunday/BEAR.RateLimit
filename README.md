# BEAR.RateLimit

Rate limiting for BEAR.Sunday.

The limit is bound by AOP onto `ResourceObject` methods, and a rejection is a
`BEAR\Resource` 4xx exception, so the framework's own error pipeline serves the
response. That coupling is deliberate, and it is why this is a `bear/` package.

## Installation

```bash
composer require bear/rate-limit
```

## Module

Install `RateLimitModule` in your application module. The store is chosen by
name:

```php
use BEAR\RateLimit\RateLimitModule;

$this->install(RateLimitModule::withSqlite('sqlite:/var/lib/app/rate-limit.sqlite'));
```

```php
$this->install(RateLimitModule::withInMemory());
```

The module binds `ClockInterface` (PSR-20), `ThrottleInterface` and
`RemoteAddressInterface`. Bind any of them in your own module to replace it; your
binding wins whether it is made before or after `install()`.

## Resource attribute

```php
use BEAR\RateLimit\Attribute\RateLimit;

class Login extends ResourceObject
{
    #[RateLimit(limit: 10, interval: '30 minutes')]
    public function onPost(string $email, string $password): static
    {
        return $this;
    }
}
```

Both arguments are required, so the policy is always visible on the method.
`interval` is a PHP relative format without a sign (`'30 minutes'`, `'1 hour'`,
`'10 seconds'`); it is applied as `'+' . $interval`.

The class must not be `final`. Ray.Di applies interceptors through a generated
subclass and skips a `final` class without an error, so the limit would never
run.

Put the attribute on the Page methods a browser reaches, not on App resources
reached from the CLI or through internal requests. Those calls are counted too:
every internal request counts against the browser's address, and a CLI call has
no remote address, so all CLI callers share one counter.

## Key

A request is counted under the method that carries the attribute plus the
remote address. Two attributed methods on one class, such as `onGet` and
`onPost`, have separate counters.

The URI is not part of the key. `AppAdapter` maps `/login`, `/-login` and
`/login?x=1` to the same `Login::onPost`, and they share one counter, so a
client cannot reset its count by respelling the path or varying the query.
When there is no remote address, all address-less requests to a method share
one counter.

## Window

Windows are fixed. The first counted request opens a window of `interval`;
later requests do not extend it. When the window expires, the count starts again
from zero. A client can therefore send up to `2 × limit` requests across a window
boundary.

A rejected request is not counted, so retrying while limited does not prolong
the limit.

## Rejection

A request beyond the limit throws
`BEAR\RateLimit\Exception\TooManyRequestsException`, a `BadRequestException`
with code `429`, before the Resource method runs. No `Retry-After` header is set.

## Remote address

`ServerRemoteAddress` reads `REMOTE_ADDR` only. Forwarding headers such as
`X-Forwarded-For` are not read, because any client can forge them. Behind a
trusted proxy, implement `RemoteAddressInterface` to resolve the client past
the proxy and bind it:

```php
$this->bind(RemoteAddressInterface::class)->to(MyProxyAwareRemoteAddress::class);
```

## Storage

`withSqlite()` takes a PDO DSN. Use a file DSN that every PHP worker can write:
the counts are shared through the file, so the limit holds across PHP-FPM
workers. The `throttle` table is created on first use. `sqlite::memory:` is
private to one connection and does not share counts.

`withInMemory()` keeps the counts in the process. It is for tests and
single-process demos, not for production.

## Testing

Install `withInMemory()` and bind a fixed clock, so a test can reach the limit
and step past the window without waiting:

```php
$this->install(RateLimitModule::withInMemory());
$this->bind(ClockInterface::class)->toInstance($fixedClock);
```

## Known limitations

- `isExceeded()` and `countUp()` are not one transaction, so concurrent requests
  can exceed the limit slightly.
- Expired SQLite rows are overwritten on the next request for the same key, never
  deleted.
- Rotating addresses within an IPv6 `/64` evades a per-address limit. Normalise
  the prefix in a `RemoteAddressInterface` implementation; the package does not
  ship one yet.
- No `Retry-After` header is returned.
