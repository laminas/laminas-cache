<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage;

use IteratorAggregate;

/**
 * @method IteratorInterface getIterator() Get the storage iterator
 */
interface IterableInterface extends IteratorAggregate
{
}
