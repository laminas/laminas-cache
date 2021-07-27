<?php
declare(strict_types=1);

namespace LaminasTest\Cache\Psr\CacheItemPool\TestAsset;

use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;

interface FlushableStorageAdapterInterface extends StorageInterface, FlushableInterface
{

}
