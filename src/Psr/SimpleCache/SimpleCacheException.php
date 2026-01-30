<?php

namespace Laminas\Cache\Psr\SimpleCache;

use Psr\SimpleCache\CacheException as PsrCacheException;
use RuntimeException;

/** @final */
class SimpleCacheException extends RuntimeException implements PsrCacheException
{
}
