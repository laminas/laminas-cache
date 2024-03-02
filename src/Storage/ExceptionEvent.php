<?php

namespace Laminas\Cache\Storage;

use ArrayObject;
use Throwable;

class ExceptionEvent extends PostEvent
{
    /**
     * The exception to be thrown
     */
    protected Throwable $throwable;

    /**
     * Throw the exception or use the result
     */
    protected bool $throwException = true;

    /**
     * Accept a target and its parameters.
     *
     * @param non-empty-string $name Event name
     */
    public function __construct(
        string $name,
        StorageInterface $storage,
        ArrayObject $params,
        mixed $result,
        Throwable $throwable,
    ) {
        parent::__construct($name, $storage, $params, $result);
        $this->setThrowable($throwable);
    }

    /**
     * Set the exception to be thrown
     */
    public function setThrowable(Throwable $throwable): self
    {
        $this->throwable = $throwable;
        return $this;
    }

    /**
     * Get the exception to be thrown
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

    /**
     * Throw the exception or use the result
     */
    public function setThrowException(bool $flag): self
    {
        $this->throwException = $flag;
        return $this;
    }

    /**
     * Throw the exception or use the result
     */
    public function getThrowException(): bool
    {
        return $this->throwException;
    }
}
