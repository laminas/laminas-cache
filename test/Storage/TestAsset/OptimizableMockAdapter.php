<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\OptimizableInterface;

class OptimizableMockAdapter extends MockAdapter implements OptimizableInterface
{
    public function optimize()
    {
    }
}
