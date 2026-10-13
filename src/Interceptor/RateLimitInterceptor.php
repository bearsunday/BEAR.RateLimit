<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Interceptor;

use BEAR\RateLimit\Attribute\RateLimit;
use BEAR\RateLimit\Exception\TooManyRequestsException;
use BEAR\RateLimit\Http\RemoteAddressInterface;
use BEAR\RateLimit\Throttle\ThrottleInterface;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use ReflectionMethod;

use function assert;
use function sha1;

final readonly class RateLimitInterceptor implements MethodInterceptor
{
    public function __construct(
        private ThrottleInterface $throttle,
        private RemoteAddressInterface $remoteAddress,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $method = $invocation->getMethod();
        $policy = $method->getAnnotation(RateLimit::class);
        assert($policy instanceof RateLimit);
        $key = $this->key($method);

        if ($this->throttle->isExceeded($key, $policy->limit)) {
            throw new TooManyRequestsException();
        }

        $this->throttle->countUp($key, $policy->interval);

        return $invocation->proceed();
    }

    private function key(ReflectionMethod $method): string
    {
        $address = $this->remoteAddress->address() ?? '';

        return sha1("{$method->class}::{$method->name}|{$address}");
    }
}
