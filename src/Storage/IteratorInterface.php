<?php

namespace Laminas\Cache\Storage;

use Iterator;

/**
 * @template-covariant TKey
 * @template-covariant TValue
 * @template-extends Iterator<TKey, TValue>
 */
interface IteratorInterface extends Iterator
{
    public const CURRENT_AS_SELF  = 0;
    public const CURRENT_AS_KEY   = 1;
    public const CURRENT_AS_VALUE = 2;

    /**
     * Get storage instance
     */
    public function getStorage(): StorageInterface;

    /**
     * Get iterator mode
     *
     * @return IteratorInterface::CURRENT_AS_*
     */
    public function getMode(): int;

    /**
     * Set iterator mode
     *
     * @param IteratorInterface::CURRENT_AS_* $mode
     */
    public function setMode(int $mode): self;
}
