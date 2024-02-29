<?php

namespace Laminas\Cache\Storage;

interface TaggableInterface
{
    /**
     * Set tags to an item by given key.
     * An empty array will remove all tags.
     *
     * @param string[] $tags
     */
    public function setTags(string $key, array $tags): bool;

    /**
     * Get tags of an item by given key
     *
     * @return string[]|false
     */
    public function getTags(string $key): false|array;

    /**
     * Remove items matching given tags.
     *
     * If $disjunction only one of the given tags must match
     * else all given tags must match.
     *
     * @param string[] $tags
     */
    public function clearByTags(array $tags, bool $disjunction = false): bool;
}
