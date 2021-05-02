<?php

declare(strict_types=1);

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\StorageInterface;

abstract class AbstractStorageCapablePattern extends AbstractPattern implements StorageCapableInterface
{
    /**
     * @var StorageInterface|null
     */
    protected $storage;

    public function __construct(?StorageInterface $storage = null, ?PatternOptions $options = null)
    {
        parent::__construct($options);
        $this->storage = $storage;
        $this->assertStorageMatchesStorageFromOptions($storage, $options);
    }

    private function assertStorageMatchesStorageFromOptions(?StorageInterface $storage, ?PatternOptions $options): void
    {
        if ($storage === null) {
            return;
        }

        if ($options === null) {
            return;
        }

        $storageViaOptions = $options->getStorage();
        if ($storageViaOptions === null) {
            return;
        }

        if ($storageViaOptions === $storage) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Storage passed to %s is not the same as passed to the %s.',
            self::class,
            PatternOptions::class
        ));
    }

    public function setOptions(PatternOptions $options)
    {
        $this->assertStorageMatchesStorageFromOptions($this->storage, $options);
        return parent::setOptions($options);
    }

    public function getStorage(): ?StorageInterface
    {
        $options = $this->getOptions();
        $storage = $options->getStorage();
        if ($storage !== null) {
            return $storage;
        }

        return $this->storage;
    }
}
