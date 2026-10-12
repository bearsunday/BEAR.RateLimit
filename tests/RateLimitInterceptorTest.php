<?php

declare(strict_types=1);

namespace BEAR\RateLimit;

use BEAR\RateLimit\Exception\TooManyRequestsException;
use BEAR\RateLimit\Fake\FakeClock;
use BEAR\RateLimit\Fake\FakeRemoteAddress;
use BEAR\RateLimit\Fake\RateLimitedResource;
use BEAR\RateLimit\Interceptor\RateLimitInterceptor;
use BEAR\RateLimit\Throttle\InMemoryThrottle;
use BEAR\Resource\Uri;
use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectiveMethodInvocation;

final class RateLimitInterceptorTest extends TestCase
{
    private const URI = 'page://self/limited';

    private FakeClock $clock;
    private FakeRemoteAddress $address;
    private RateLimitInterceptor $interceptor;

    protected function setUp(): void
    {
        $this->clock = new FakeClock();
        $this->address = new FakeRemoteAddress();
        $this->interceptor = new RateLimitInterceptor(new InMemoryThrottle($this->clock), $this->address);
    }

    public function testAttemptsWithinTheLimitProceed(): void
    {
        $target = new RateLimitedResource();

        $this->reachLimit($target);

        $this->assertSame(2, $target->reached);
    }

    public function testAttemptBeyondTheLimitIsRejectedBeforeTheResourceRuns(): void
    {
        $target = new RateLimitedResource();
        $this->reachLimit($target);

        $this->assertTrue($this->rejected($target, self::URI));

        $this->assertSame(2, $target->reached);
    }

    public function testAnotherAddressHasItsOwnCounter(): void
    {
        $target = new RateLimitedResource();
        $this->reachLimit($target);

        $this->address->address = '192.0.2.1';

        $this->assertFalse($this->rejected($target, self::URI));
    }

    public function testAnotherPathHasItsOwnCounter(): void
    {
        $target = new RateLimitedResource();
        $this->reachLimit($target);

        $this->assertFalse($this->rejected($target, 'page://self/other'));
    }

    public function testUrisThatDifferOnlyInQueryShareOneCounter(): void
    {
        $target = new RateLimitedResource();
        $this->attempt($target, self::URI . '?code=a&state=1');
        $this->attempt($target, self::URI . '?code=b&state=2');

        $this->assertTrue($this->rejected($target, self::URI . '?code=c&state=3'));
    }

    public function testMissingAddressIsStillLimited(): void
    {
        $this->address->address = null;
        $target = new RateLimitedResource();
        $this->reachLimit($target);

        $this->assertTrue($this->rejected($target, self::URI));
    }

    public function testAttemptsProceedAgainWhenTheAttributeIntervalPasses(): void
    {
        $target = new RateLimitedResource();
        $this->reachLimit($target);

        $this->clock->advance('1 minute');

        $this->assertFalse($this->rejected($target, self::URI));
    }

    private function reachLimit(RateLimitedResource $target): void
    {
        $this->attempt($target, self::URI);
        $this->attempt($target, self::URI);
    }

    private function rejected(RateLimitedResource $target, string $uri): bool
    {
        try {
            $this->attempt($target, $uri);
        } catch (TooManyRequestsException) {
            return true;
        }

        return false;
    }

    private function attempt(RateLimitedResource $target, string $uri): void
    {
        $target->uri = new Uri($uri);

        (new ReflectiveMethodInvocation($target, 'onGet', [], [$this->interceptor]))->proceed();
    }
}
