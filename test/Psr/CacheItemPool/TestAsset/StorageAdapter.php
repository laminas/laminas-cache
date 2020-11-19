<?php
declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\CacheItemPool\TestAsset;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\FlushableInterface;

class StorageAdapter extends AbstractAdapter implements FlushableInterface, ClearByNamespaceInterface
{

    protected function internalGetItem(&$normalizedKey, &$success = null, &$casToken = null)
    {
    }

    protected function internalSetItem(&$normalizedKey, &$value)
    {
    }

    protected function internalRemoveItem(&$normalizedKey)
    {
    }

    public function clearByNamespace($namespace)
    {
    }

    public function flush()
    {
    }
}
