<?php
/**
 * @see       https://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-cache/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Cache\Psr;

use Zend\Cache\Storage\StorageInterface;

/**
 * Provides common functionality surrounding value de/serialization as required
 * by both PSR-6 and PSR-16
 */
trait SerializationTrait
{
    /**
     * @var bool
     */
    private $serializeValues = false;

    /**
     * @var string
     */
    private static $serializedFalse;

    /**
     * Determine if the given storage adapter requires serialization.
     *
     * Determines if the given storage adapter requires serialization. If so,
     * set $serializeValues to true, and serialize a boolean false for later
     * comparisons.
     *
     * @param StorageInterface $storage
     * @return void
     */
    private function memoizeSerializationCapabilities(StorageInterface $storage)
    {
        $capabilities = $storage->getCapabilities();
        $requiredTypes = ['string', 'integer', 'double', 'boolean', 'NULL', 'array', 'object'];
        $types = $capabilities->getSupportedDatatypes();
        $shouldSerialize = false;

        foreach ($requiredTypes as $type) {
            // 'object' => 'object' is OK
            // 'integer' => 'string' is not (redis)
            // 'integer' => 'integer' is not (memcache)
            if (! (isset($types[$type]) && in_array($types[$type], [true, 'array', 'object'], true))) {
                $shouldSerialize = true;
                break;
            }
        }

        if ($shouldSerialize) {
            static::$serializedFalse = serialize(false);
        }

        $this->serializeValues = $shouldSerialize;
    }

    /**
     * Unserialize a value retrieved from the cache.
     *
     * @param string $value
     * @return mixed
     */
    abstract public function unserialize($value);
}
