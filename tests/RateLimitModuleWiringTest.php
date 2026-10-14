<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use BEAR\RateLimit\Exception\TooManyRequestsException;
use BEAR\RateLimit\Fake\AppBindingsModule;
use BEAR\RateLimit\Fake\FakeClock;
use BEAR\RateLimit\Fake\FakeRemoteAddress;
use BEAR\RateLimit\Fake\RateLimitedResource;
use BEAR\RateLimit\Http\RemoteAddressInterface;
use BEAR\RateLimit\Http\ServerRemoteAddress;
use BEAR\RateLimit\Throttle\InMemoryThrottle;
use BEAR\RateLimit\Throttle\SqliteThrottle;
use BEAR\RateLimit\Throttle\ThrottleInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function bin2hex;
use function mkdir;
use function random_bytes;
use function sys_get_temp_dir;

final class RateLimitModuleWiringTest extends TestCase
{
    public function testRequestBeyondTheLimitIsRejected(): void
    {
        $resource = $this->injector(RateLimitModule::withInMemory())->getInstance(RateLimitedResource::class);
        $resource->onGet();
        $resource->onGet();

        try {
            $resource->onGet();
            $this->fail('The third request in the window was not rejected.');
        } catch (TooManyRequestsException) {
            $this->assertSame(2, $resource->reached);
        }
    }

    public function testWithSqliteBindsSqliteThrottle(): void
    {
        $throttle = $this->injector(RateLimitModule::withSqlite('sqlite::memory:'))->getInstance(ThrottleInterface::class);

        $this->assertInstanceOf(SqliteThrottle::class, $throttle);
    }

    public function testRemoteAddressDefaultsToServerRemoteAddress(): void
    {
        $address = $this->injector(RateLimitModule::withInMemory())->getInstance(RemoteAddressInterface::class);

        $this->assertInstanceOf(ServerRemoteAddress::class, $address);
    }

    /** @return array<string, array{bool}> */
    public static function bindingOrderProvider(): array
    {
        return [
            'bound before install' => [true],
            'bound after install' => [false],
        ];
    }

    #[DataProvider('bindingOrderProvider')]
    public function testAppBindingsWinOverTheModule(bool $bindBeforeInstall): void
    {
        $injector = $this->injector(new AppBindingsModule($bindBeforeInstall));

        $this->assertInstanceOf(FakeClock::class, $injector->getInstance(ClockInterface::class));
        $this->assertInstanceOf(InMemoryThrottle::class, $injector->getInstance(ThrottleInterface::class));
        $this->assertInstanceOf(FakeRemoteAddress::class, $injector->getInstance(RemoteAddressInterface::class));
    }

    private function injector(AbstractModule $module): Injector
    {
        $tmpDir = sys_get_temp_dir() . '/bear-rate-limit-aop-' . bin2hex(random_bytes(6));
        mkdir($tmpDir);

        return new Injector($module, $tmpDir);
    }
}
