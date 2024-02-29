<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\OptimizableInterface;

/**
 * @template TOptions of AdapterOptions
 * @template-extends MockAdapter<TOptions>
 */
class OptimizableMockAdapter extends MockAdapter implements OptimizableInterface
{
    public function optimize(): bool
    {
        return true;
    }
}
