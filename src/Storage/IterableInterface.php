<?php

namespace Laminas\Cache\Storage;

use IteratorAggregate;

/**
 * @method IteratorInterface getIterator() Get the storage iterator
 */
interface IterableInterface extends IteratorAggregate
{
}
