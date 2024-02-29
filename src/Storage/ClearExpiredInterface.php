<?php

namespace Laminas\Cache\Storage;

interface ClearExpiredInterface
{
    /**
     * Remove expired items
     */
    public function clearExpired(): bool;
}
