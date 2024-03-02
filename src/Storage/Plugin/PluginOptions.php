<?php

namespace Laminas\Cache\Storage\Plugin;

use Laminas\Cache\Exception;
use Laminas\Serializer\Adapter\AdapterInterface as SerializerAdapter;
use Laminas\Serializer\Serializer as SerializerFactory;
use Laminas\Stdlib\AbstractOptions;
use Webmozart\Assert\Assert;

use function is_callable;

class PluginOptions extends AbstractOptions
{
    /**
     * Used by:
     * - ClearByFactor
     */
    protected int $clearingFactor = 0;

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
     */
    protected bool $exitOnAbort = true;

    /**
     * Used by:
     * - OptimizeByFactor
     */
    protected int $optimizingFactor = 0;

    /**
     * Used by:
     * - Serializer
     * @phpcs:disable WebimpressCodingStandard.Classes.NoNullValues.Invalid
     */
    protected SerializerAdapter|string|null $serializer = null;

    /**
     * Used by:
     * - Serializer
     */
    protected array $serializerOptions = [];

    /**
     * Used by:
     * - ExceptionHandler
     */
    protected bool $throwExceptions = true;

    /**
     * Set automatic clearing factor
     *
     * Used by:
     * - ClearExpiredByFactor
     */
    public function setClearingFactor(int $clearingFactor): self
    {
        $this->clearingFactor = $this->normalizeFactor($clearingFactor);
        return $this;
    }

    /**
     * Get automatic clearing factor
     *
     * Used by:
     * - ClearExpiredByFactor
     */
    public function getClearingFactor(): int
    {
        return $this->clearingFactor;
    }

    /**
     * Set callback to call on intercepted exception
     *
     * Used by:
     * - ExceptionHandler
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setExceptionCallback(null|callable $exceptionCallback): self
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
     */
    public function getExceptionCallback(): callable|null
    {
        return $this->exceptionCallback;
    }

    /**
     * Exit if connection aborted and ignore_user_abort is disabled.
     */
    public function setExitOnAbort(bool $exitOnAbort): self
    {
        $this->exitOnAbort = $exitOnAbort;
        return $this;
    }

    /**
     * Exit if connection aborted and ignore_user_abort is disabled.
     */
    public function getExitOnAbort(): bool
    {
        return $this->exitOnAbort;
    }

    /**
     * Set automatic optimizing factor
     *
     * Used by:
     * - OptimizeByFactor
     */
    public function setOptimizingFactor(int $optimizingFactor): self
    {
        $this->optimizingFactor = $this->normalizeFactor($optimizingFactor);
        return $this;
    }

    /**
     * Set automatic optimizing factor
     *
     * Used by:
     * - OptimizeByFactor
     */
    public function getOptimizingFactor(): int
    {
        return $this->optimizingFactor;
    }

    /**
     * Set serializer
     *
     * Used by:
     * - Serializer
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setSerializer(string|SerializerAdapter $serializer): self
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * Get serializer
     *
     * Used by:
     * - Serializer
     */
    public function getSerializer(): SerializerAdapter
    {
        if (! $this->serializer instanceof SerializerAdapter) {
            // use default serializer
            if ($this->serializer === null || $this->serializer === '') {
                $this->setSerializer(SerializerFactory::getDefaultAdapter());
            // instantiate by class name + serializer_options
            } else {
                $options = $this->getSerializerOptions();
                $this->setSerializer(SerializerFactory::factory($this->serializer, $options));
            }
        }
        Assert::notNull($this->serializer);
        return $this->serializer;
    }

    /**
     * Set configuration options for instantiating a serializer adapter
     *
     * Used by:
     * - Serializer
     */
    public function setSerializerOptions(array $serializerOptions): self
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
    public function getSerializerOptions(): array
    {
        return $this->serializerOptions;
    }

    /**
     * Set flag indicating we should re-throw exceptions
     *
     * Used by:
     * - ExceptionHandler
     */
    public function setThrowExceptions(bool $throwExceptions): self
    {
        $this->throwExceptions = $throwExceptions;
        return $this;
    }

    /**
     * Should we re-throw exceptions?
     *
     * Used by:
     * - ExceptionHandler
     */
    public function getThrowExceptions(): bool
    {
        return $this->throwExceptions;
    }

    /**
     * Normalize a factor
     *
     * Cast to int and ensure we have a value greater than zero.
     *
     * @return non-negative-int
     * @throws Exception\InvalidArgumentException
     */
    protected function normalizeFactor(int $factor): int
    {
        if ($factor < 0) {
            throw new Exception\InvalidArgumentException(
                "Invalid factor '{$factor}': must be greater or equal 0"
            );
        }
        return $factor;
    }
}
