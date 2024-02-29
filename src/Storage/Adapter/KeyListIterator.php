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
 * @template-implements IteratorInterface<non-empty-string, TValue>
 */
class KeyListIterator implements IteratorInterface, Countable
{
    /**
     * The iterator mode
     *
     * @var IteratorInterface::CURRENT_AS_*
     */
    protected int $mode = IteratorInterface::CURRENT_AS_KEY;

    /**
     * Number of keys
     */
    protected int $count;

    /**
     * Current iterator position
     */
    protected int $position = 0;

    /**
     * @param array<int,non-empty-string> $keys Keys to iterate over
     */
    public function __construct(
        protected StorageInterface $storage,
        protected array $keys
    ) {
        $this->count = count($keys);
    }

    /**
     * Get storage instance
     */
    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * Get iterator mode
     *
     * @return IteratorInterface::CURRENT_AS_*
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * Set iterator mode
     *
     * @param IteratorInterface::CURRENT_AS_* $mode
     */
    public function setMode(int $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get current key, value or metadata.
     */
    public function current(): mixed
    {
        if ($this->mode === IteratorInterface::CURRENT_AS_SELF) {
            return $this;
        }

        $key = $this->key();

        if ($this->mode === IteratorInterface::CURRENT_AS_VALUE) {
            return $this->storage->getItem($key);
        }

        return $key;
    }

    /**
     * Get current key
     *
     * @return non-empty-string
     */
    public function key(): string
    {
        return $this->keys[$this->position];
    }

    /**
     * Checks if current position is valid
     */
    public function valid(): bool
    {
        return $this->position < $this->count;
    }

    /**
     * Move forward to next element
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Count number of items
     */
    public function count(): int
    {
        return $this->count;
    }
}
