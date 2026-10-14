<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use BEAR\RateLimit\Fake\FakeClock;
use BEAR\RateLimit\Throttle\SqliteDsn;
use BEAR\RateLimit\Throttle\SqliteThrottle;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function is_file;
use function random_bytes;
use function sys_get_temp_dir;
use function unlink;

final class SqliteThrottleTest extends TestCase
{
    private const KEY = 'page://self/login|127.0.0.1';
    private const INTERVAL = '1 minute';

    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir() . '/bear-rate-limit-' . bin2hex(random_bytes(6)) . '.sqlite';
    }

    protected function tearDown(): void
    {
        if (! is_file($this->file)) {
            return;
        }

        unlink($this->file);
    }

    public function testInstancesOnOneFileShareTheCount(): void
    {
        $clock = new FakeClock();
        $dsn = new SqliteDsn('sqlite:' . $this->file);
        $worker1 = new SqliteThrottle($dsn, $clock);
        $worker2 = new SqliteThrottle($dsn, $clock);

        $worker1->countUp(self::KEY, self::INTERVAL);
        $worker2->countUp(self::KEY, self::INTERVAL);

        $this->assertTrue($worker1->isExceeded(self::KEY, 2));
        $this->assertTrue($worker2->isExceeded(self::KEY, 2));
    }
}
