<?php
declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter\TestAsset;

use Laminas\Cache\Storage\Adapter\AdapterOptions;

final class AdapterOptionsWithPrioritizedOptions extends AdapterOptions
{
    protected $__prioritizedProperties__ = [
        'key_pattern',
        'namespace',
    ];
}
