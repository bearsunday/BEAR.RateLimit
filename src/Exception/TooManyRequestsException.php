<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Exception;

use BEAR\Resource\Exception\BadRequestException;
use Throwable;

final class TooManyRequestsException extends BadRequestException
{
    public const STATUS = 429;

    public function __construct(string $message = 'Too Many Requests.', Throwable|null $previous = null)
    {
        parent::__construct($message, self::STATUS, $previous);
    }
}
