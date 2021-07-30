<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\TestAsset;

use Laminas\Cache\Storage\ClearByNamespaceInterface;

interface FlushableNamespaceStorageInterface extends FlushableStorageInterface, ClearByNamespaceInterface
{
}
