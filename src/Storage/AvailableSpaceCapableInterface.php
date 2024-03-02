<?php

namespace Laminas\Cache\Storage;

interface AvailableSpaceCapableInterface
{
    /**
     * Get available space in bytes
     */
    public function getAvailableSpace(): int;
}
