<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Storage\StorageInterface;

interface StorageCapableInterface extends PatternInterface
{
    public function getStorage(): ?StorageInterface;
}
