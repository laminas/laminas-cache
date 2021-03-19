<?php

namespace LaminasBench\Cache;

use Laminas\Cache\StorageFactory;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;

/**
 * @Revs(100)
 * @Iterations(10)
 * @Warmup(1)
 */
class MemoryStorageAdapterBenchAbstract extends AbstractCommonStorageAdapterBench
{
    public function __construct()
    {
        // instantiate the storage adapter
        $this->storage = StorageFactory::adapterFactory(MockAdapter::class);

        parent::__construct();
    }
}
