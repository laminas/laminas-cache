<?php

declare(strict_types=1);

namespace LaminasTest\Cache\StaticAnalysis;

use Laminas\Cache\Storage\AbstractMetadataCapableAdapter;

/**
 * @template-extends AbstractMetadataCapableAdapter<object{meta:string}>
 */
final class MetadataCapableAdapter extends AbstractMetadataCapableAdapter
{
    /**
     * {@inheritDoc}
     */
    protected function internalHasItem(&$normalizedKey)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetItem(&$normalizedKey, &$success = null, mixed &$casToken = null)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalSetItem(&$normalizedKey, mixed &$value)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalRemoveItem(&$normalizedKey)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetMetadata(string $normalizedKey): ?object
    {
        return null;
    }
}

$adapter  = new MetadataCapableAdapter();
$metadata = $adapter->getMetadata('foo');

if ($metadata === null) {
    return;
}

echo $metadata->meta;
