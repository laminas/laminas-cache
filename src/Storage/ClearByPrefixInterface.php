<?php

namespace Laminas\Cache\Storage;

interface ClearByPrefixInterface
{
    /**
     * Remove items matching given prefix
     */
    public function clearByPrefix(string $prefix): bool;
}
