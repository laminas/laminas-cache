<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;
use Laminas\Stdlib\AbstractOptions;
use LogicException;

use function array_intersect;
use function array_map;
use function array_unique;
use function array_values;
use function gettype;
use function is_dir;
use function is_object;
use function is_readable;
use function is_string;
use function is_writable;
use function octdec;
use function realpath;
use function rtrim;
use function sprintf;
use function stripos;

use const DIRECTORY_SEPARATOR;
use const PHP_OS;

/**
 * @template-extends AbstractOptions<mixed>
 */
final class PatternOptions extends AbstractOptions
{
    /**
     * Used by:
     * - ObjectCache
     */
    protected bool $cacheByDefault = true;

    /**
     * Used by:
     * - CallbackCache
     * - ObjectCache
     */
    protected bool $cacheOutput = true;

    /**
     * Used by:
     * - CaptureCache
     */
    protected false|int $umask = false;

    /**
     * Used by:
     * - CaptureCache
     */
    protected false|int $dirPermission = 0700;

    /**
     * Used by:
     * - CaptureCache
     */
    protected false|int $filePermission = 0600;

    /**
     * Used by:
     * - CaptureCache
     */
    protected bool $fileLocking = true;

    /**
     * Used by:
     * - CaptureCache
     */
    protected string $indexFilename = 'index.html';

    /**
     * Used by:
     * - ObjectCache
     */
    protected ?object $object = null;

    /**
     * Used by:
     * - ObjectCache
     */
    protected bool $objectCacheMagicProperties = false;

    /**
     * Used by:
     * - ObjectCache
     */
    protected array $objectCacheMethods = [];

    /**
     * Used by:
     * - ObjectCache
     */
    protected ?string $objectKey = null;

    /**
     * Used by:
     * - ObjectCache
     */
    protected array $objectNonCacheMethods = ['__tostring'];

    /**
     * Used by:
     * - CaptureCache
     */
    protected ?string $publicDir = null;

    /**
     * @param iterable<string,mixed>|null $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(iterable|null $options = null)
    {
        // disable file/directory permissions by default on windows systems
        if (stripos(PHP_OS, 'WIN') === 0) {
            $this->filePermission = false;
            $this->dirPermission  = false;
        }

        parent::__construct($options);
    }

    /**
     * Set flag indicating whether or not to cache by default
     *
     * Used by:
     * - ObjectCache
     */
    public function setCacheByDefault(bool $cacheByDefault): self
    {
        $this->cacheByDefault = $cacheByDefault;
        return $this;
    }

    /**
     * Do we cache by default?
     *
     * Used by:
     * - ObjectCache
     */
    public function getCacheByDefault(): bool
    {
        return $this->cacheByDefault;
    }

    /**
     * Set whether or not to cache output
     *
     * Used by:
     * - CallbackCache
     * - ObjectCache
     */
    public function setCacheOutput(bool $cacheOutput): self
    {
        $this->cacheOutput = $cacheOutput;
        return $this;
    }

    /**
     * Will we cache output?
     *
     * Used by:
     * - CallbackCache
     * - ObjectCache
     */
    public function getCacheOutput(): bool
    {
        return $this->cacheOutput;
    }

    /**
     * Set directory permission
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setDirPermission(false|float|int|string $dirPermission): self
    {
        if ($dirPermission !== false) {
            if (is_string($dirPermission)) {
                $dirPermission = octdec($dirPermission);
            } else {
                $dirPermission = (int) $dirPermission;
            }

            /**
             * Code is untested, applying strict type check might lead to unexpected errors.
             *
             * @phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
             */
            if (($dirPermission & 0700) != 0700) {
                throw new Exception\InvalidArgumentException(
                    'Invalid directory permission: need permission to execute, read and write by owner'
                );
            }
        }

        $this->dirPermission = $dirPermission;
        return $this;
    }

    /**
     * Gets directory permission
     */
    public function getDirPermission(): int|false
    {
        return $this->dirPermission;
    }

    /**
     * Set umask
     *
     * Used by:
     * - CaptureCache
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setUmask(false|float|int|string $umask): self
    {
        if ($umask !== false) {
            if (is_string($umask)) {
                $umask = octdec($umask);
            } else {
                $umask = (int) $umask;
            }

            // validate
            if (($umask & 0700) !== 0) {
                throw new Exception\InvalidArgumentException(
                    'Invalid umask: need permission to execute, read and write by owner'
                );
            }

            // normalize
            $umask &= ~0002;
        }

        $this->umask = $umask;
        return $this;
    }

    /**
     * Get umask
     *
     * Used by:
     * - CaptureCache
     */
    public function getUmask(): int|false
    {
        return $this->umask;
    }

    /**
     * Set whether or not file locking should be used
     *
     * Used by:
     * - CaptureCache
     */
    public function setFileLocking(bool $fileLocking): self
    {
        $this->fileLocking = $fileLocking;
        return $this;
    }

    /**
     * Is file locking enabled?
     *
     * Used by:
     * - CaptureCache
     */
    public function getFileLocking(): bool
    {
        return $this->fileLocking;
    }

    /**
     * Set file permission
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setFilePermission(false|float|int|string $filePermission): self
    {
        if ($filePermission !== false) {
            if (is_string($filePermission)) {
                $filePermission = octdec($filePermission);
            } else {
                $filePermission = (int) $filePermission;
            }

            /**
             * Code is untested, applying strict type check might lead to unexpected errors.
             *
             * @phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
             */
            if (($filePermission & 0600) != 0600) {
                throw new Exception\InvalidArgumentException(
                    'Invalid file permission: need permission to read and write by owner'
                );
            }

            if (($filePermission & 0111) !== 0) {
                throw new Exception\InvalidArgumentException(
                    "Invalid file permission: Files shouldn't be executable"
                );
            }
        }

        $this->filePermission = $filePermission;
        return $this;
    }

    /**
     * Gets file permission
     */
    public function getFilePermission(): false|int
    {
        return $this->filePermission;
    }

    /**
     * Set value for index filename
     */
    public function setIndexFilename(string $indexFilename): self
    {
        $this->indexFilename = $indexFilename;
        return $this;
    }

    /**
     * Get value for index filename
     */
    public function getIndexFilename(): string
    {
        return $this->indexFilename;
    }

    /**
     * Set object to cache
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setObject(mixed $object): self
    {
        if (! is_object($object)) {
            throw new Exception\InvalidArgumentException(
                sprintf('%s expects an object; received "%s"', __METHOD__, gettype($object))
            );
        }
        $this->object = $object;
        return $this;
    }

    /**
     * Get object to cache
     */
    public function getObject(): null|object
    {
        return $this->object;
    }

    /**
     * Set flag indicating whether or not to cache magic properties
     *
     * Used by:
     * - ObjectCache
     */
    public function setObjectCacheMagicProperties(bool $objectCacheMagicProperties): self
    {
        $this->objectCacheMagicProperties = $objectCacheMagicProperties;
        return $this;
    }

    /**
     * Should we cache magic properties?
     *
     * Used by:
     * - ObjectCache
     */
    public function getObjectCacheMagicProperties(): bool
    {
        return $this->objectCacheMagicProperties;
    }

    /**
     * Set list of object methods for which to cache return values
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setObjectCacheMethods(array $objectCacheMethods): self
    {
        $this->objectCacheMethods = $this->normalizeObjectMethods($objectCacheMethods);
        return $this;
    }

    /**
     * Get list of object methods for which to cache return values
     *
     * @return array
     */
    public function getObjectCacheMethods(): array
    {
        return $this->objectCacheMethods;
    }

    /**
     * Set the object key part.
     *
     * Used to generate a callback key in order to speed up key generation.
     *
     * Used by:
     * - ObjectCache
     *
     * @param  null|string $objectKey The object key or NULL to use the objects class name
     */
    public function setObjectKey(null|string $objectKey): self
    {
        $this->objectKey = $objectKey;
        return $this;
    }

    /**
     * Get object key
     *
     * Used by:
     * - ObjectCache
     */
    public function getObjectKey(): string
    {
        if ($this->objectKey !== null) {
            return $this->objectKey;
        }

        $object = $this->getObject();

        if ($object === null) {
            throw new LogicException('Missing `object` to detect object key.');
        }

        return $object::class;
    }

    /**
     * Set list of object methods for which NOT to cache return values
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setObjectNonCacheMethods(array $objectNonCacheMethods): self
    {
        $this->objectNonCacheMethods = $this->normalizeObjectMethods($objectNonCacheMethods);
        return $this;
    }

    /**
     * Get list of object methods for which NOT to cache return values
     *
     * @return array
     */
    public function getObjectNonCacheMethods(): array
    {
        return $this->objectNonCacheMethods;
    }

    /**
     * Set location of public directory
     *
     * Used by:
     * - CaptureCache
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setPublicDir(string $publicDir): self
    {
        if (! is_dir($publicDir)) {
            throw new Exception\InvalidArgumentException(
                "Public directory '{$publicDir}' not found or not a directory"
            );
        } elseif (! is_writable($publicDir)) {
            throw new Exception\InvalidArgumentException(
                "Public directory '{$publicDir}' not writable"
            );
        } elseif (! is_readable($publicDir)) {
            throw new Exception\InvalidArgumentException(
                "Public directory '{$publicDir}' not readable"
            );
        }

        $this->publicDir = rtrim(realpath($publicDir), DIRECTORY_SEPARATOR);
        return $this;
    }

    /**
     * Get location of public directory
     *
     * Used by:
     * - CaptureCache
     */
    public function getPublicDir(): null|string
    {
        return $this->publicDir;
    }

    /**
     * Recursively apply strtolower on all values of an array, and return as a
     * list of unique values
     *
     * @return array
     */
    protected function recursiveStrtolower(array $array): array
    {
        return array_values(array_unique(array_map('strtolower', $array)));
    }

    /**
     * Normalize object methods
     *
     * Recursively casts values to lowercase, then determines if any are in a
     * list of methods not handled, raising an exception if so.
     *
     * @param  array $methods
     * @return array
     * @throws Exception\InvalidArgumentException
     */
    protected function normalizeObjectMethods(array $methods): array
    {
        $methods   = $this->recursiveStrtolower($methods);
        $intersect = array_intersect(['__set', '__get', '__unset', '__isset'], $methods);
        if (! empty($intersect)) {
            throw new Exception\InvalidArgumentException(
                "Magic properties are handled by option 'cache_magic_properties'"
            );
        }
        return $methods;
    }
}
