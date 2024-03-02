<?php

declare(strict_types=1);

namespace LaminasTest\Cache\StaticAnalysis;

use Laminas\Cache\Storage\AbstractMetadataCapableAdapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;

/**
 * @uses AdapterOptions
 *
 * @template-extends AbstractMetadataCapableAdapter<AdapterOptions,object{meta:string}>
 */
final class MetadataCapableAdapter extends AbstractMetadataCapableAdapter
{
    /**
     * {@inheritDoc}
     */
    protected function internalHasItem(string $normalizedKey): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetItem(string $normalizedKey, ?bool &$success = null, mixed &$casToken = null): mixed
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalSetItem(string $normalizedKey, mixed $value): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalRemoveItem(string $normalizedKey): bool
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

    /**
     * @psalm-api Mark method as API method to prevent psalm from detecting this method as unused.
     */
    public function whatever(): string
    {
        $adapter  = new MetadataCapableAdapter();
        $metadata = $adapter->getMetadata('foo');

        if ($metadata === null) {
            return '';
        }

        return $metadata->meta;
    }
}
