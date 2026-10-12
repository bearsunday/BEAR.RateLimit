<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Fake;

use BEAR\RateLimit\RateLimitModule;
use BEAR\RateLimit\Throttle\InMemoryThrottle;
use BEAR\RateLimit\Throttle\ThrottleInterface;
use Override;
use Psr\Clock\ClockInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class AppBindingsModule extends AbstractModule
{
    public function __construct(
        private readonly bool $bindBeforeInstall,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->bindBeforeInstall) {
            $this->bindAppServices();
            $this->install(RateLimitModule::withSqlite('sqlite::memory:'));

            return;
        }

        $this->install(RateLimitModule::withSqlite('sqlite::memory:'));
        $this->bindAppServices();
    }

    private function bindAppServices(): void
    {
        $this->bind(ClockInterface::class)->to(FakeClock::class)->in(Scope::SINGLETON);
        $this->bind(ThrottleInterface::class)->to(InMemoryThrottle::class)->in(Scope::SINGLETON);
    }
}
