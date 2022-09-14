<?php

namespace Laminas\Cache\Storage;

interface ClearExpiredInterface
{
    /**
     * Remove expired items
     *
     * @return bool
     */
    public function clearExpired();
}
