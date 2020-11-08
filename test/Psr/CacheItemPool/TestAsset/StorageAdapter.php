<?php
declare(strict_types=1);

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
