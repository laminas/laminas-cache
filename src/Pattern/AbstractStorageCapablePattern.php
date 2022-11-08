<?php

declare(strict_types=1);

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Storage\StorageInterface;

abstract class AbstractStorageCapablePattern extends AbstractPattern implements StorageCapableInterface
{
    public function __construct(protected StorageInterface $storage, ?PatternOptions $options = null)
    {
        parent::__construct($options);
    }

    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }
}
