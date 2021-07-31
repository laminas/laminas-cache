<?php

namespace LaminasBench\Cache;

use Laminas\Cache\Storage\Adapter\Memory;

/**
 * @Revs(100)
 * @Iterations(10)
 * @Warmup(1)
 */
class MemoryStorageAdapterBench extends AbstractCommonStorageAdapterBench
{
    public function __construct()
    {
        // instantiate the storage adapter
        $this->storage = new Memory();

        parent::__construct();
    }
}
