<?php

namespace Laminas\Cache\Storage;

interface TotalSpaceCapableInterface
{
    /**
     * Get total space in bytes
     *
     * @return int|float
     */
    public function getTotalSpace();
}
