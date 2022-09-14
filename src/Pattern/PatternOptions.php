<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;
use Laminas\Stdlib\AbstractOptions;
use Traversable;

use function array_intersect;
use function array_map;
use function array_unique;
use function array_values;
use function get_class;
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

class PatternOptions extends AbstractOptions
{
    /**
     * Used by:
     * - ObjectCache
     *
     * @var bool
     */
    protected $cacheByDefault = true;

    /**
     * Used by:
     * - CallbackCache
     * - ObjectCache
     *
     * @var bool
     */
    protected $cacheOutput = true;

    /**
     * Used by:
     * - CaptureCache
     *
     * @var false|int
     */
    protected $umask = false;

    /**
     * Used by:
     * - CaptureCache
     *
     * @var false|int
     */
    protected $dirPermission = 0700;

    /**
     * Used by:
     * - CaptureCache
     *
     * @var false|int
     */
    protected $filePermission = 0600;

    /**
     * Used by:
     * - CaptureCache
     *
     * @var bool
     */
    protected $fileLocking = true;

    /**
     * Used by:
     * - CaptureCache
     *
     * @var string
     */
    protected $indexFilename = 'index.html';

    /**
     * Used by:
     * - ObjectCache
     *
     * @var null|object
     */
    protected $object;

    /**
     * Used by:
     * - ObjectCache
     *
     * @var bool
     */
    protected $objectCacheMagicProperties = false;

    /**
     * Used by:
     * - ObjectCache
     *
     * @var array
     */
    protected $objectCacheMethods = [];

    /**
     * Used by:
     * - ObjectCache
     *
     * @var null|string
     */
    protected $objectKey;

    /**
     * Used by:
     * - ObjectCache
     *
     * @var array
     */
    protected $objectNonCacheMethods = ['__tostring'];

    /**
     * Used by:
     * - CaptureCache
     *
     * @var null|string
     */
    protected $publicDir;

    /**
     * Constructor
     *
     * @param  array|Traversable|null $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = null)
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
     *
     * @param  bool $cacheByDefault
     * @return PatternOptions Provides a fluent interface
     */
    public function setCacheByDefault($cacheByDefault)
    {
        $this->cacheByDefault = $cacheByDefault;
        return $this;
    }

    /**
     * Do we cache by default?
     *
     * Used by:
     * - ObjectCache
     *
     * @return bool
     */
    public function getCacheByDefault()
    {
        return $this->cacheByDefault;
    }

    /**
     * Set whether or not to cache output
     *
     * Used by:
     * - CallbackCache
     * - ObjectCache
     *
     * @param  bool $cacheOutput
     * @return PatternOptions Provides a fluent interface
     */
    public function setCacheOutput($cacheOutput)
    {
        $this->cacheOutput = (bool) $cacheOutput;
        return $this;
    }

    /**
     * Will we cache output?
     *
     * Used by:
     * - CallbackCache
     * - ObjectCache
     *
     * @return bool
     */
    public function getCacheOutput()
    {
        return $this->cacheOutput;
    }

    /**
     * Set directory permission
     *
     * @param  false|int|string|float $dirPermission
     * @throws Exception\InvalidArgumentException
     * @return PatternOptions Provides a fluent interface
     */
    public function setDirPermission($dirPermission)
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
     *
     * @return false|int
     */
    public function getDirPermission()
    {
        return $this->dirPermission;
    }

    /**
     * Set umask
     *
     * Used by:
     * - CaptureCache
     *
     * @param  false|int|string|float $umask
     * @throws Exception\InvalidArgumentException
     * @return PatternOptions Provides a fluent interface
     */
    public function setUmask($umask)
    {
        if ($umask !== false) {
            if (is_string($umask)) {
                $umask = octdec($umask);
            } else {
                $umask = (int) $umask;
            }

            // validate
            if ($umask & 0700) {
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
     *
     * @return false|int
     */
    public function getUmask()
    {
        return $this->umask;
    }

    /**
     * Set whether or not file locking should be used
     *
     * Used by:
     * - CaptureCache
     *
     * @param  bool $fileLocking
     * @return PatternOptions Provides a fluent interface
     */
    public function setFileLocking($fileLocking)
    {
        $this->fileLocking = (bool) $fileLocking;
        return $this;
    }

    /**
     * Is file locking enabled?
     *
     * Used by:
     * - CaptureCache
     *
     * @return bool
     */
    public function getFileLocking()
    {
        return $this->fileLocking;
    }

    /**
     * Set file permission
     *
     * @param  false|int|string|float $filePermission
     * @throws Exception\InvalidArgumentException
     * @return PatternOptions Provides a fluent interface
     */
    public function setFilePermission($filePermission)
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

            if ($filePermission & 0111) {
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
     *
     * @return false|int
     */
    public function getFilePermission()
    {
        return $this->filePermission;
    }

    /**
     * Set value for index filename
     *
     * @param  string $indexFilename
     * @return PatternOptions Provides a fluent interface
     */
    public function setIndexFilename($indexFilename)
    {
        $this->indexFilename = (string) $indexFilename;
        return $this;
    }

    /**
     * Get value for index filename
     *
     * @return string
     */
    public function getIndexFilename()
    {
        return $this->indexFilename;
    }

    /**
     * Set object to cache
     *
     * @param  mixed $object
     * @throws Exception\InvalidArgumentException
     * @return PatternOptions Provides a fluent interface
     */
    public function setObject($object)
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
     *
     * @return null|object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set flag indicating whether or not to cache magic properties
     *
     * Used by:
     * - ObjectCache
     *
     * @param  bool $objectCacheMagicProperties
     * @return PatternOptions Provides a fluent interface
     */
    public function setObjectCacheMagicProperties($objectCacheMagicProperties)
    {
        $this->objectCacheMagicProperties = (bool) $objectCacheMagicProperties;
        return $this;
    }

    /**
     * Should we cache magic properties?
     *
     * Used by:
     * - ObjectCache
     *
     * @return bool
     */
    public function getObjectCacheMagicProperties()
    {
        return $this->objectCacheMagicProperties;
    }

    /**
     * Set list of object methods for which to cache return values
     *
     * @param  array $objectCacheMethods
     * @return PatternOptions Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setObjectCacheMethods(array $objectCacheMethods)
    {
        $this->objectCacheMethods = $this->normalizeObjectMethods($objectCacheMethods);
        return $this;
    }

    /**
     * Get list of object methods for which to cache return values
     *
     * @return array
     */
    public function getObjectCacheMethods()
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
     * @return PatternOptions Provides a fluent interface
     */
    public function setObjectKey($objectKey)
    {
        if ($objectKey !== null) {
            $this->objectKey = (string) $objectKey;
        } else {
            $this->objectKey = null;
        }
        return $this;
    }

    /**
     * Get object key
     *
     * Used by:
     * - ObjectCache
     *
     * @return string
     */
    public function getObjectKey()
    {
        if ($this->objectKey === null) {
            return get_class($this->getObject());
        }
        return $this->objectKey;
    }

    /**
     * Set list of object methods for which NOT to cache return values
     *
     * @param  array $objectNonCacheMethods
     * @return PatternOptions Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setObjectNonCacheMethods(array $objectNonCacheMethods)
    {
        $this->objectNonCacheMethods = $this->normalizeObjectMethods($objectNonCacheMethods);
        return $this;
    }

    /**
     * Get list of object methods for which NOT to cache return values
     *
     * @return array
     */
    public function getObjectNonCacheMethods()
    {
        return $this->objectNonCacheMethods;
    }

    /**
     * Set location of public directory
     *
     * Used by:
     * - CaptureCache
     *
     * @param  string $publicDir
     * @throws Exception\InvalidArgumentException
     * @return PatternOptions Provides a fluent interface
     */
    public function setPublicDir($publicDir)
    {
        $publicDir = (string) $publicDir;

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
     *
     * @return null|string
     */
    public function getPublicDir()
    {
        return $this->publicDir;
    }

    /**
     * Recursively apply strtolower on all values of an array, and return as a
     * list of unique values
     *
     * @param  array $array
     * @return array
     */
    protected function recursiveStrtolower(array $array)
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
    protected function normalizeObjectMethods(array $methods)
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
