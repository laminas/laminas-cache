<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventsCapableInterface;

interface EventsCapableStorageInterface extends EventsCapableInterface, StorageInterface
{
}
