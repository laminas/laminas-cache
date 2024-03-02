<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage;

use Laminas\Cache\Exception\ExceptionInterface;

/**
 * @template TMetadata of object
 */
interface MetadataCapableInterface
{
    /**
     * Get metadata of an item.
     *
     * @return TMetadata|null Metadata on success, null on failure or in case metadata is not accessible.
     * @throws ExceptionInterface
     */
    public function getMetadata(string $key): ?object;

    /**
     * Get multiple metadata
     *
     * @param non-empty-list<non-empty-string> $keys
     * @return array<string,TMetadata> Associative array of keys and metadata
     * @throws ExceptionInterface
     */
    public function getMetadatas(array $keys): array;
}
