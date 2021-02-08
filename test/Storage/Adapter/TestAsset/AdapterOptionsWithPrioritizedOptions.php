<?php
declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-cache-storage-adapter-test for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache-storage-adapter-test/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache-storage-adapter-test/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter\TestAsset;

use Laminas\Cache\Storage\Adapter\AdapterOptions;

final class AdapterOptionsWithPrioritizedOptions extends AdapterOptions
{
    // @codingStandardsIgnoreStart
    protected $__prioritizedProperties__ = [
        'key_pattern',
        'namespace',
    ];
    // @codingStandardsIgnoreEnd
}
