<?php

namespace Laminas\Cache\Storage;

use ArrayObject;

class PostEvent extends Event
{
    /**
     * The result/return value
     *
     * @var mixed
     */
    protected $result;

    /**
     * Constructor
     *
     * Accept a target and its parameters.
     *
     * @param  string           $name
     * @param  mixed            $result
     */
    public function __construct($name, StorageInterface $storage, ArrayObject $params, &$result)
    {
        parent::__construct($name, $storage, $params);
        $this->setResult($result);
    }

    /**
     * Set the result/return value
     *
     * @return PostEvent
     */
    public function setResult(mixed &$value)
    {
        $this->result = &$value;
        return $this;
    }

    /**
     * Get the result/return value
     *
     * @return mixed
     */
    public function &getResult()
    {
        return $this->result;
    }
}
