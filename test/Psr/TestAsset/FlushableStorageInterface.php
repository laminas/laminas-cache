<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\TestAsset;

use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\PluginAwareInterface;
use Laminas\Cache\Storage\StorageInterface;

interface FlushableStorageInterface extends StorageInterface, FlushableInterface, PluginAwareInterface
{
}
