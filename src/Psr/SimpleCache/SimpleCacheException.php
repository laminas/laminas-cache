<?php

namespace Laminas\Cache\Psr\SimpleCache;

use Psr\SimpleCache\CacheException as PsrCacheException;
use RuntimeException;

class SimpleCacheException extends RuntimeException implements PsrCacheException
{
}
