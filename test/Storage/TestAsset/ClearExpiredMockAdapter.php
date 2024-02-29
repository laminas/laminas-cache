<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\ClearExpiredInterface;

/**
 * @template TOptions of AdapterOptions
 * @template-extends MockAdapter<TOptions>
 */
class ClearExpiredMockAdapter extends MockAdapter implements ClearExpiredInterface
{
    public function clearExpired(): bool
    {
        return true;
    }
}
