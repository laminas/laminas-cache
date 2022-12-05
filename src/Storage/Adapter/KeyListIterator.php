<?php

namespace Laminas\Cache\Storage\Adapter;

use Countable;
use Laminas\Cache\Storage\IteratorInterface;
use Laminas\Cache\Storage\StorageInterface;
use ReturnTypeWillChange;

use function count;

/**
 * @see ReturnTypeWillChange
 *
 * @template-covariant TValue
 * @template-implements IteratorInterface<string, TValue>
 */
class KeyListIterator implements IteratorInterface, Countable
{
    /**
     * The iterator mode
     *
     * @var int
     */
    protected $mode = IteratorInterface::CURRENT_AS_KEY;

    /**
     * Number of keys
     *
     * @var int
     */
    protected $count;

    /**
     * Current iterator position
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Constructor
     *
     * @param string[] $keys Keys to iterate over
     */
    public function __construct(
        protected StorageInterface $storage,
        protected array $keys
    ) {
        $this->count = count($keys);
    }

    /**
     * Get storage instance
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Get iterator mode
     *
     * @return int Value of IteratorInterface::CURRENT_AS_*
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set iterator mode
     *
     * @param int $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = (int) $mode;
        return $this;
    }

    /**
     * Get current key, value or metadata.
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        if ($this->mode === IteratorInterface::CURRENT_AS_SELF) {
            return $this;
        }

        $key = $this->key();

        if ($this->mode === IteratorInterface::CURRENT_AS_METADATA) {
            return $this->storage->getMetadata($key);
        }

        if ($this->mode === IteratorInterface::CURRENT_AS_VALUE) {
            return $this->storage->getItem($key);
        }

        return $key;
    }

    /**
     * Get current key
     *
     * @return string
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->keys[$this->position];
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->position < $this->count;
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        $this->position++;
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Count number of items
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return $this->count;
    }
}
