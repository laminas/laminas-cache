<?php

namespace Laminas\Cache\Psr\CacheItemPool;

use Psr\Cache\CacheException as CacheExceptionInterface;
use RuntimeException;

class CacheException extends RuntimeException implements CacheExceptionInterface
{
}
