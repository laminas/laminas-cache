<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\TestAsset;

use Laminas\Cache\Storage\ClearByNamespaceInterface;

interface FlushableNamespaceStorageInterface extends FlushableStorageInterface, ClearByNamespaceInterface
{
}
