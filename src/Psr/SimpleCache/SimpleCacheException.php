<?php

declare(strict_types=1);

namespace Laminas\Cache\Psr\SimpleCache;

use Psr\SimpleCache\CacheException as PsrCacheException;
use RuntimeException;

class SimpleCacheException extends RuntimeException implements PsrCacheException
{
}
