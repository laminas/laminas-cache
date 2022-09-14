<?php

namespace Laminas\Cache\Storage;

interface OptimizableInterface
{
    /**
     * Optimize the storage
     *
     * @return bool
     */
    public function optimize();
}
