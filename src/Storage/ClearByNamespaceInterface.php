<?php

namespace Laminas\Cache\Storage;

interface ClearByNamespaceInterface
{
    /**
     * Remove items of given namespace
     */
    public function clearByNamespace(string $namespace): bool;
}
