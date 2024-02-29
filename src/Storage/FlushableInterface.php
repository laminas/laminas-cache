<?php

namespace Laminas\Cache\Storage;

interface FlushableInterface
{
    /**
     * Flush the whole storage
     */
    public function flush(): bool;
}
