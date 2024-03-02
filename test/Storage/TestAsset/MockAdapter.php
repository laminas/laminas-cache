<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;

/**
 * @template TOptions of AdapterOptions
 * @template-extends AbstractAdapter<TOptions>
 */
class MockAdapter extends AbstractAdapter
{
    protected function internalGetItem(string $normalizedKey, ?bool &$success = null, mixed &$casToken = null): mixed
    {
        return null;
    }

    protected function internalSetItem(string $normalizedKey, mixed $value): bool
    {
        return true;
    }

    protected function internalRemoveItem(string $normalizedKey): bool
    {
        return true;
    }
}
