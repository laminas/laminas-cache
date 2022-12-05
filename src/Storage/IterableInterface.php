<?php

namespace Laminas\Cache\Storage;

use IteratorAggregate;

/**
 * @method IteratorInterface getIterator() Get the storage iterator
 * @template-covariant TKey
 * @template-covariant TValue
 * @template-extends IteratorAggregate<TKey, TValue>
 */
interface IterableInterface extends IteratorAggregate
{
}
