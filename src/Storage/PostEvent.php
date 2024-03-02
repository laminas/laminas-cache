<?php

namespace Laminas\Cache\Storage;

use ArrayObject;

class PostEvent extends Event
{
    /**
     * The result/return value
     */
    protected mixed $result;

    /**
     * Accept a target and its parameters.
     *
     * @param non-empty-string $name Event name
     * @param ArrayObject<string,mixed> $params
     */
    public function __construct(string $name, StorageInterface $storage, ArrayObject $params, mixed $result)
    {
        parent::__construct($name, $storage, $params);
        $this->setResult($result);
    }

    /**
     * Set the result/return value
     */
    public function setResult(mixed $value): self
    {
        $this->result = $value;
        return $this;
    }

    /**
     * Get the result/return value
     */
    public function getResult(): mixed
    {
        return $this->result;
    }
}
