<?php

namespace Laminas\Cache\Storage;

interface TotalSpaceCapableInterface
{
    /**
     * Get total space in bytes
     */
    public function getTotalSpace(): int;
}
