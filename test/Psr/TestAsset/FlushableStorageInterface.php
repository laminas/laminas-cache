<?php
declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\TestAsset;

use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\PluginAwareInterface;
use Laminas\Cache\Storage\StorageInterface;

interface FlushableStorageInterface extends StorageInterface, FlushableInterface, PluginAwareInterface
{

}
