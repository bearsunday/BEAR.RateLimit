# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `#[RateLimit(limit, interval)]` — per-method fixed-window limit on a `ResourceObject`.
- `RateLimitModule::withSqlite()` and `RateLimitModule::withInMemory()` — DI wiring with the store chosen by name.
- `RateLimitInterceptor` — counts by the attributed method and remote address, and rejects before the method runs.
- `TooManyRequestsException` — a `BadRequestException` with code 429.
- `ThrottleInterface` with `SqliteThrottle` (configured by `SqliteDsn`) and `InMemoryThrottle`.
- `RemoteAddressInterface` with `ServerRemoteAddress`, which reads `REMOTE_ADDR` only.
- `SystemClock` — PSR-20 clock in UTC.

### Known limitations

- `isExceeded()` and `countUp()` are not one transaction, so concurrent requests can exceed the limit slightly.
- Expired SQLite rows are overwritten on the next request for the same key, never deleted.
- Rotating addresses within an IPv6 `/64` evades a per-address limit; prefix normalisation belongs in a `RemoteAddressInterface` implementation, which the package does not ship yet.
- No `Retry-After` header is returned.
- `#[RateLimit]` on a `final` class is skipped without an error, because Ray.Di weaves interceptors through a subclass.
