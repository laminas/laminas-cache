<?php

declare(strict_types=1);

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Storage\StorageInterface;

abstract class AbstractStorageCapablePattern extends AbstractPattern implements StorageCapableInterface
{
    /** @var StorageInterface */
    protected $storage;

    public function __construct(StorageInterface $storage, ?PatternOptions $options = null)
    {
        parent::__construct($options);
        $this->storage = $storage;
    }

    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }
}
