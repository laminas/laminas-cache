<?php

namespace Laminas\Cache\Storage;

use Iterator;

interface IteratorInterface extends Iterator
{
    public const CURRENT_AS_SELF     = 0;
    public const CURRENT_AS_KEY      = 1;
    public const CURRENT_AS_VALUE    = 2;
    public const CURRENT_AS_METADATA = 3;

    /**
     * Get storage instance
     *
     * @return StorageInterface
     */
    public function getStorage();

    /**
     * Get iterator mode
     *
     * @return int Value of IteratorInterface::CURRENT_AS_*
     */
    public function getMode();

    /**
     * Set iterator mode
     *
     * @param int $mode Value of IteratorInterface::CURRENT_AS_*
     * @return IteratorInterface Fluent interface
     */
    public function setMode($mode);
}
