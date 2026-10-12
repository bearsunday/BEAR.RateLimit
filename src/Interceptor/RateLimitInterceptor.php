<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Interceptor;

use BEAR\RateLimit\Attribute\RateLimit;
use BEAR\RateLimit\Exception\TooManyRequestsException;
use BEAR\RateLimit\Http\RemoteAddressInterface;
use BEAR\RateLimit\Throttle\ThrottleInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

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
        $policy = $invocation->getMethod()->getAnnotation(RateLimit::class);
        assert($policy instanceof RateLimit);
        $key = $this->key($invocation);

        if ($this->throttle->isExceeded($key, $policy->limit)) {
            throw new TooManyRequestsException();
        }

        $this->throttle->countUp($key, $policy->interval);

        return $invocation->proceed();
    }

    /** @param MethodInvocation<object> $invocation */
    private function key(MethodInvocation $invocation): string
    {
        $resource = $invocation->getThis();
        assert($resource instanceof ResourceObject);
        $uri = $resource->uri;
        $address = $this->remoteAddress->address() ?? '';

        return sha1("{$uri->scheme}://{$uri->host}{$uri->path}|{$address}");
    }
}
