<?php

namespace Laminas\Cache\Psr\SimpleCache;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

class SimpleCacheInvalidArgumentException extends InvalidArgumentException implements PsrInvalidArgumentException
{
}
