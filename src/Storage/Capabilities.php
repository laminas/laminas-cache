<?php

namespace Laminas\Cache\Storage;

use ArrayObject;
use Laminas\Cache\Exception;
use Laminas\EventManager\EventsCapableInterface;
use stdClass;

use function array_diff;
use function array_keys;
use function in_array;
use function is_string;
use function strtolower;

class Capabilities
{
    public const UNKNOWN_KEY_LENGTH   = -1;
    public const UNLIMITED_KEY_LENGTH = 0;

    /**
     * "lock-on-expire" support in seconds.
     *
     *      0 = Expired items will never be retrieved
     *     >0 = Time in seconds an expired item could be retrieved
     *     -1 = Expired items could be retrieved forever
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?bool $lockOnExpire = null;

    /**
     * Max. key length
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?int $maxKeyLength = null;

    /**
     * Min. TTL (0 means items never expire)
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?int $minTtl = null;

    /**
     * Max. TTL (0 means infinite)
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?int $maxTtl = null;

    /**
     * Namespace is prefix
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?bool $namespaceIsPrefix = null;

    /**
     * Namespace separator
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?string $namespaceSeparator = null;

    /**
     * Static ttl
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?bool $staticTtl = null;

    /**
     * Supported datatypes
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     *
     * @var null|array
     */
    protected ?array $supportedDatatypes = null;

    /**
     * TTL precision
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?int $ttlPrecision = null;

    /**
     * Use request time
     *
     * If it's NULL the capability isn't set and the getter
     * returns the base capability or the default value.
     */
    protected ?bool $useRequestTime = null;

    public function __construct(
        protected StorageInterface $storage,
        /**
         * A marker to set/change capabilities
         */
        protected stdClass $marker,
        array $capabilities = [],
        protected ?Capabilities $baseCapabilities = null
    ) {
        foreach ($capabilities as $name => $value) {
            $this->setCapability($marker, $name, $value);
        }
    }

    /**
     * Get the storage adapter
     */
    public function getAdapter(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * Get supported datatypes
     */
    public function getSupportedDatatypes(): array
    {
        return $this->getCapability('supportedDatatypes', [
            'NULL'     => false,
            'boolean'  => false,
            'integer'  => false,
            'double'   => false,
            'string'   => true,
            'array'    => false,
            'object'   => false,
            'resource' => false,
        ]);
    }

    /**
     * Set supported datatypes
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setSupportedDatatypes(stdClass $marker, array $datatypes): self
    {
        $allTypes = [
            'array',
            'boolean',
            'double',
            'integer',
            'NULL',
            'object',
            'resource',
            'string',
        ];

        // check/normalize datatype values
        $normalized = [];
        foreach ($datatypes as $type => $toType) {
            if (! in_array($type, $allTypes)) {
                throw new Exception\InvalidArgumentException("Unknown datatype '{$type}'");
            }

            if (is_string($toType)) {
                $toType = strtolower($toType);
                if (! in_array($toType, $allTypes)) {
                    throw new Exception\InvalidArgumentException("Unknown datatype '{$toType}'");
                }
            } else {
                $toType = (bool) $toType;
            }

            $normalized[$type] = $toType;
        }

        // add missing datatypes as not supported
        $missingTypes = array_diff($allTypes, array_keys($normalized));
        foreach ($missingTypes as $type) {
            $normalized[$type] = false;
        }

        return $this->setCapability($marker, 'supportedDatatypes', $normalized);
    }

    /**
     * Get minimum supported time-to-live
     *
     * @return int 0 means items never expire
     */
    public function getMinTtl(): int
    {
        return $this->getCapability('minTtl', 0);
    }

    /**
     * Set minimum supported time-to-live
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setMinTtl(stdClass $marker, int $minTtl): self
    {
        if ($minTtl < 0) {
            throw new Exception\InvalidArgumentException('$minTtl must be greater or equal 0');
        }
        return $this->setCapability($marker, 'minTtl', $minTtl);
    }

    /**
     * Get maximum supported time-to-live
     *
     * @return int 0 means infinite
     */
    public function getMaxTtl(): int
    {
        return $this->getCapability('maxTtl', 0);
    }

    /**
     * Set maximum supported time-to-live
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setMaxTtl(stdClass $marker, int $maxTtl): self
    {
        if ($maxTtl < 0) {
            throw new Exception\InvalidArgumentException('$maxTtl must be greater or equal 0');
        }
        return $this->setCapability($marker, 'maxTtl', $maxTtl);
    }

    /**
     * Is the time-to-live handled static (on write)
     * or dynamic (on read)
     */
    public function getStaticTtl(): bool
    {
        return $this->getCapability('staticTtl', false);
    }

    /**
     * Set if the time-to-live handled static (on write) or dynamic (on read)
     */
    public function setStaticTtl(stdClass $marker, bool $flag): self
    {
        return $this->setCapability($marker, 'staticTtl', $flag);
    }

    /**
     * Get time-to-live precision
     */
    public function getTtlPrecision(): float
    {
        return $this->getCapability('ttlPrecision', 1);
    }

    /**
     * Set time-to-live precision
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setTtlPrecision(stdClass $marker, float $ttlPrecision): self
    {
        if ($ttlPrecision <= 0) {
            throw new Exception\InvalidArgumentException('$ttlPrecision must be greater than 0');
        }
        return $this->setCapability($marker, 'ttlPrecision', $ttlPrecision);
    }

    /**
     * Get use request time
     */
    public function getUseRequestTime(): bool
    {
        return $this->getCapability('useRequestTime', false);
    }

    /**
     * Set use request time
     */
    public function setUseRequestTime(stdClass $marker, bool $flag): self
    {
        return $this->setCapability($marker, 'useRequestTime', $flag);
    }

    /**
     * Get "lock-on-expire" support in seconds.
     *
     * @return int 0  = Expired items will never be retrieved
     *             >0 = Time in seconds an expired item could be retrieved
     *             -1 = Expired items could be retrieved forever
     */
    public function getLockOnExpire(): int
    {
        return $this->getCapability('lockOnExpire', 0);
    }

    /**
     * Set "lock-on-expire" support in seconds.
     */
    public function setLockOnExpire(stdClass $marker, int $timeout): self
    {
        return $this->setCapability($marker, 'lockOnExpire', $timeout);
    }

    /**
     * Get maximum key length
     *
     * @return int -1 means unknown, 0 means infinite
     */
    public function getMaxKeyLength(): int
    {
        return $this->getCapability('maxKeyLength', self::UNKNOWN_KEY_LENGTH);
    }

    /**
     * Set maximum key length
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setMaxKeyLength(stdClass $marker, int $maxKeyLength): self
    {
        if ($maxKeyLength < -1) {
            throw new Exception\InvalidArgumentException('$maxKeyLength must be greater or equal than -1');
        }
        return $this->setCapability($marker, 'maxKeyLength', $maxKeyLength);
    }

    /**
     * Get if namespace support is implemented as prefix
     */
    public function getNamespaceIsPrefix(): bool
    {
        return $this->getCapability('namespaceIsPrefix', true);
    }

    /**
     * Set if namespace support is implemented as prefix
     */
    public function setNamespaceIsPrefix(stdClass $marker, bool $flag): self
    {
        return $this->setCapability($marker, 'namespaceIsPrefix', $flag);
    }

    /**
     * Get namespace separator if namespace is implemented as prefix
     */
    public function getNamespaceSeparator(): string
    {
        return $this->getCapability('namespaceSeparator', '');
    }

    /**
     * Set the namespace separator if namespace is implemented as prefix
     */
    public function setNamespaceSeparator(stdClass $marker, string $separator): self
    {
        return $this->setCapability($marker, 'namespaceSeparator', $separator);
    }

    /**
     * Get a capability
     */
    protected function getCapability(string $property, mixed $default = null): mixed
    {
        if ($this->$property !== null) {
            return $this->$property;
        } elseif ($this->baseCapabilities) {
            $getMethod = 'get' . $property;
            return $this->baseCapabilities->$getMethod();
        }
        return $default;
    }

    /**
     * Change a capability
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function setCapability(stdClass $marker, string $property, mixed $value): self
    {
        if ($this->marker !== $marker) {
            throw new Exception\InvalidArgumentException('Invalid marker');
        }

        if ($this->$property !== $value) {
            $this->$property = $value;

            // trigger event
            if ($this->storage instanceof EventsCapableInterface) {
                $this->storage->getEventManager()->trigger('capability', $this->storage, new ArrayObject([
                    $property => $value,
                ]));
            }
        }

        return $this;
    }
}
