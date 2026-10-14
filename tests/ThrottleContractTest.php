<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use BEAR\RateLimit\Fake\FakeClock;
use BEAR\RateLimit\Throttle\InMemoryThrottle;
use BEAR\RateLimit\Throttle\SqliteDsn;
use BEAR\RateLimit\Throttle\SqliteThrottle;
use BEAR\RateLimit\Throttle\ThrottleInterface;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThrottleContractTest extends TestCase
{
    private const KEY = 'page://self/admin/callback|127.0.0.1';
    private const INTERVAL = '30 minutes';
    private const LIMIT = 10;

    /** @return array<string, array{Closure(FakeClock): ThrottleInterface}> */
    public static function implementationProvider(): array
    {
        return [
            'InMemoryThrottle' => [
                static fn (FakeClock $clock): ThrottleInterface => new InMemoryThrottle($clock),
            ],
            'SqliteThrottle' => [
                static fn (FakeClock $clock): ThrottleInterface => new SqliteThrottle(
                    new SqliteDsn('sqlite::memory:'),
                    $clock,
                ),
            ],
        ];
    }

    /** @param Closure(FakeClock): ThrottleInterface $create */
    #[DataProvider('implementationProvider')]
    public function testBelowLimitIsNotExceeded(Closure $create): void
    {
        $throttle = $create(new FakeClock());

        $this->countUp($throttle, self::LIMIT - 1);

        $this->assertFalse($throttle->isExceeded(self::KEY, self::LIMIT));
    }

    /** @param Closure(FakeClock): ThrottleInterface $create */
    #[DataProvider('implementationProvider')]
    public function testReachingLimitIsExceeded(Closure $create): void
    {
        $throttle = $create(new FakeClock());

        $this->countUp($throttle, self::LIMIT);

        $this->assertTrue($throttle->isExceeded(self::KEY, self::LIMIT));
    }

    /** @param Closure(FakeClock): ThrottleInterface $create */
    #[DataProvider('implementationProvider')]
    public function testKeysAreCountedIndependently(Closure $create): void
    {
        $throttle = $create(new FakeClock());

        $this->countUp($throttle, self::LIMIT);

        $this->assertFalse($throttle->isExceeded('page://self/admin/callback|192.0.2.1', self::LIMIT));
    }

    /** @param Closure(FakeClock): ThrottleInterface $create */
    #[DataProvider('implementationProvider')]
    public function testWindowEndsAtItsExpiryInstant(Closure $create): void
    {
        $clock = new FakeClock();
        $throttle = $create($clock);
        $this->countUp($throttle, self::LIMIT);

        $clock->advance('29 minutes 59 seconds');
        $this->assertTrue($throttle->isExceeded(self::KEY, self::LIMIT));

        $clock->advance('1 second');
        $this->assertFalse($throttle->isExceeded(self::KEY, self::LIMIT));
    }

    /** @param Closure(FakeClock): ThrottleInterface $create */
    #[DataProvider('implementationProvider')]
    public function testCountUpAfterExpiryStartsANewWindowAtOne(Closure $create): void
    {
        $clock = new FakeClock();
        $throttle = $create($clock);
        $this->countUp($throttle, self::LIMIT);
        $clock->advance(self::INTERVAL);

        $throttle->countUp(self::KEY, self::INTERVAL);

        $this->assertTrue($throttle->isExceeded(self::KEY, 1));
        $this->assertFalse($throttle->isExceeded(self::KEY, 2));
    }

    /** @param Closure(FakeClock): ThrottleInterface $create */
    #[DataProvider('implementationProvider')]
    public function testLaterAttemptsDoNotExtendTheWindow(Closure $create): void
    {
        $clock = new FakeClock();
        $throttle = $create($clock);
        $throttle->countUp(self::KEY, self::INTERVAL);
        $clock->advance('20 minutes');
        $this->countUp($throttle, self::LIMIT - 1);
        $this->assertTrue($throttle->isExceeded(self::KEY, self::LIMIT));

        $clock->advance('10 minutes');

        $this->assertFalse($throttle->isExceeded(self::KEY, self::LIMIT));
    }

    private function countUp(ThrottleInterface $throttle, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $throttle->countUp(self::KEY, self::INTERVAL);
        }
    }
}
