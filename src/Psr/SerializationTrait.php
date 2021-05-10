<?php

namespace Laminas\Cache\Psr;

use Laminas\Cache\Storage\StorageInterface;

/**
 * Provides common functionality surrounding value de/serialization as required
 * by both PSR-6 and PSR-16
 */
trait SerializationTrait
{
    /**
     * Determine if the given storage adapter requires serialization.
     *
     * @param StorageInterface $storage
     * @return bool
     */
    private function isSerializationRequired(StorageInterface $storage)
    {
        $capabilities = $storage->getCapabilities();
        $requiredTypes = ['string', 'integer', 'double', 'boolean', 'NULL', 'array', 'object'];
        $types = $capabilities->getSupportedDatatypes();

        foreach ($requiredTypes as $type) {
            // 'object' => 'object' is OK
            // 'integer' => 'string' is not (redis)
            // 'integer' => 'integer' is not (memcache)
            if (! (isset($types[$type]) && in_array($types[$type], [true, 'array', 'object'], true))) {
                return true;
            }
        }

        return false;
    }
}
