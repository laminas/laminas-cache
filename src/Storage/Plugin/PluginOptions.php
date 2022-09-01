<?php

namespace Laminas\Cache\Storage\Plugin;

use Laminas\Cache\Exception;
use Laminas\Serializer\Adapter\AdapterInterface as SerializerAdapter;
use Laminas\Serializer\Serializer as SerializerFactory;
use Laminas\Stdlib\AbstractOptions;

use function gettype;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;

class PluginOptions extends AbstractOptions
{
    /**
     * Used by:
     * - ClearByFactor
     *
     * @var int
     */
    protected $clearingFactor = 0;

    /**
     * Used by:
     * - ExceptionHandler
     *
     * @var null|callable
     */
    protected $exceptionCallback;

    /**
     * Used by:
     * - IgnoreUserAbort
     *
     * @var bool
     */
    protected $exitOnAbort = true;

    /**
     * Used by:
     * - OptimizeByFactor
     *
     * @var int
     */
    protected $optimizingFactor = 0;

    /**
     * Used by:
     * - Serializer
     *
     * @var string|SerializerAdapter
     */
    protected $serializer;

    /**
     * Used by:
     * - Serializer
     *
     * @var array
     */
    protected $serializerOptions = [];

    /**
     * Used by:
     * - ExceptionHandler
     *
     * @var bool
     */
    protected $throwExceptions = true;

    /**
     * Set automatic clearing factor
     *
     * Used by:
     * - ClearExpiredByFactor
     *
     * @param  int $clearingFactor
     * @return PluginOptions Provides a fluent interface
     */
    public function setClearingFactor($clearingFactor)
    {
        $this->clearingFactor = $this->normalizeFactor($clearingFactor);
        return $this;
    }

    /**
     * Get automatic clearing factor
     *
     * Used by:
     * - ClearExpiredByFactor
     *
     * @return int
     */
    public function getClearingFactor()
    {
        return $this->clearingFactor;
    }

    /**
     * Set callback to call on intercepted exception
     *
     * Used by:
     * - ExceptionHandler
     *
     * @param  null|callable $exceptionCallback
     * @throws Exception\InvalidArgumentException
     * @return PluginOptions Provides a fluent interface
     */
    public function setExceptionCallback($exceptionCallback)
    {
        if ($exceptionCallback !== null && ! is_callable($exceptionCallback, true)) {
            throw new Exception\InvalidArgumentException('Not a valid callback');
        }
        $this->exceptionCallback = $exceptionCallback;
        return $this;
    }

    /**
     * Get callback to call on intercepted exception
     *
     * Used by:
     * - ExceptionHandler
     *
     * @return null|callable
     */
    public function getExceptionCallback()
    {
        return $this->exceptionCallback;
    }

    /**
     * Exit if connection aborted and ignore_user_abort is disabled.
     *
     * @param  bool $exitOnAbort
     * @return PluginOptions Provides a fluent interface
     */
    public function setExitOnAbort($exitOnAbort)
    {
        $this->exitOnAbort = (bool) $exitOnAbort;
        return $this;
    }

    /**
     * Exit if connection aborted and ignore_user_abort is disabled.
     *
     * @return bool
     */
    public function getExitOnAbort()
    {
        return $this->exitOnAbort;
    }

    /**
     * Set automatic optimizing factor
     *
     * Used by:
     * - OptimizeByFactor
     *
     * @param  int $optimizingFactor
     * @return PluginOptions Provides a fluent interface
     */
    public function setOptimizingFactor($optimizingFactor)
    {
        $this->optimizingFactor = $this->normalizeFactor($optimizingFactor);
        return $this;
    }

    /**
     * Set automatic optimizing factor
     *
     * Used by:
     * - OptimizeByFactor
     *
     * @return int
     */
    public function getOptimizingFactor()
    {
        return $this->optimizingFactor;
    }

    /**
     * Set serializer
     *
     * Used by:
     * - Serializer
     *
     * @param  string|SerializerAdapter $serializer
     * @throws Exception\InvalidArgumentException
     * @return PluginOptions Provides a fluent interface
     */
    public function setSerializer($serializer)
    {
        if (! is_string($serializer) && ! $serializer instanceof SerializerAdapter) {
            /**
             * @psalm-suppress RedundantConditionGivenDocblockType, DocblockTypeContradiction
             * Until we do lack native type-hint we should check the `$serializer` twice.
             */
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects either a string serializer name or Laminas\Serializer\Adapter\AdapterInterface instance; '
                . 'received "%s"',
                __METHOD__,
                is_object($serializer) ? $serializer::class : gettype($serializer)
            ));
        }
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * Get serializer
     *
     * Used by:
     * - Serializer
     *
     * @return SerializerAdapter
     */
    public function getSerializer()
    {
        if (! $this->serializer instanceof SerializerAdapter) {
            // use default serializer
            if (! $this->serializer) {
                $this->setSerializer(SerializerFactory::getDefaultAdapter());
            // instantiate by class name + serializer_options
            } else {
                $options = $this->getSerializerOptions();
                $this->setSerializer(SerializerFactory::factory($this->serializer, $options));
            }
        }
        return $this->serializer;
    }

    /**
     * Set configuration options for instantiating a serializer adapter
     *
     * Used by:
     * - Serializer
     *
     * @param  mixed $serializerOptions
     * @return PluginOptions Provides a fluent interface
     */
    public function setSerializerOptions($serializerOptions)
    {
        $this->serializerOptions = $serializerOptions;
        return $this;
    }

    /**
     * Get configuration options for instantiating a serializer adapter
     *
     * Used by:
     * - Serializer
     *
     * @return array
     */
    public function getSerializerOptions()
    {
        return $this->serializerOptions;
    }

    /**
     * Set flag indicating we should re-throw exceptions
     *
     * Used by:
     * - ExceptionHandler
     *
     * @param  bool $throwExceptions
     * @return PluginOptions Provides a fluent interface
     */
    public function setThrowExceptions($throwExceptions)
    {
        $this->throwExceptions = (bool) $throwExceptions;
        return $this;
    }

    /**
     * Should we re-throw exceptions?
     *
     * Used by:
     * - ExceptionHandler
     *
     * @return bool
     */
    public function getThrowExceptions()
    {
        return $this->throwExceptions;
    }

    /**
     * Normalize a factor
     *
     * Cast to int and ensure we have a value greater than zero.
     *
     * @param  int $factor
     * @return int
     * @throws Exception\InvalidArgumentException
     */
    protected function normalizeFactor($factor)
    {
        $factor = (int) $factor;
        if ($factor < 0) {
            throw new Exception\InvalidArgumentException(
                "Invalid factor '{$factor}': must be greater or equal 0"
            );
        }
        return $factor;
    }
}
