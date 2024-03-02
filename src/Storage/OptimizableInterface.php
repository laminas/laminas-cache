<?php

namespace Laminas\Cache\Storage;

interface OptimizableInterface
{
    /**
     * Optimize the storage
     */
    public function optimize(): bool;
}
