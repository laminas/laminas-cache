<?php

namespace Laminas\Cache\Storage;

use IteratorAggregate;

/**
 * @template-covariant TKey
 * @template-covariant TValue
 * @template-extends IteratorAggregate<TKey, TValue>
 */
interface IterableInterface extends IteratorAggregate
{
}
