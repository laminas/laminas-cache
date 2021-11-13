<?php

namespace Laminas\Cache\Psr\CacheItemPool;

use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionInterface;

use function sprintf;

class InvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentExceptionInterface
{
    public static function maximumKeyLengthExceeded(string $key, int $maximumKeyLength): self
    {
        return new self(sprintf(
            'Invalid key "%s" provided; key is too long. Must be no more than %d characters',
            $key,
            $maximumKeyLength
        ));
    }
}
