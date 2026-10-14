<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use BEAR\RateLimit\Attribute\RateLimit;
use BEAR\RateLimit\Http\RemoteAddressInterface;
use BEAR\RateLimit\Http\ServerRemoteAddress;
use BEAR\RateLimit\Interceptor\RateLimitInterceptor;
use BEAR\RateLimit\Throttle\InMemoryThrottle;
use BEAR\RateLimit\Throttle\SqliteDsn;
use BEAR\RateLimit\Throttle\SqliteThrottle;
use BEAR\RateLimit\Throttle\ThrottleInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Psr\Clock\ClockInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class RateLimitModule extends AbstractModule
{
    private function __construct(
        private readonly SqliteDsn|null $sqliteDsn,
    ) {
        parent::__construct();
    }

    /** @param string $dsn A file DSN such as `sqlite:/var/lib/app/rate-limit.sqlite`, so every worker shares the counts. */
    public static function withSqlite(string $dsn): self
    {
        return new self(new SqliteDsn($dsn));
    }

    public static function withInMemory(): self
    {
        return new self(null);
    }

    #[Override]
    protected function configure(): void
    {
        $this->bindThrottle();
        $this->bind(RemoteAddressInterface::class)->to(ServerRemoteAddress::class)->in(Scope::SINGLETON);
        $this->bind(ClockInterface::class)->to(SystemClock::class)->in(Scope::SINGLETON);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(RateLimit::class),
            [RateLimitInterceptor::class],
        );
    }

    private function bindThrottle(): void
    {
        if ($this->sqliteDsn === null) {
            $this->bind(ThrottleInterface::class)->to(InMemoryThrottle::class)->in(Scope::SINGLETON);

            return;
        }

        $this->bind(SqliteDsn::class)->toInstance($this->sqliteDsn);
        $this->bind(ThrottleInterface::class)->to(SqliteThrottle::class)->in(Scope::SINGLETON);
    }
}
