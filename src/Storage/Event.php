<?php

namespace Laminas\Cache\Storage;

use ArrayObject;
use Laminas\EventManager\Event as BaseEvent;

class Event extends BaseEvent
{
    /**
     * Constructor
     *
     * Accept a storage adapter and its parameters.
     *
     * @param  string           $name Event name
     */
    public function __construct($name, StorageInterface $storage, ArrayObject $params)
    {
        parent::__construct($name, $storage, $params);
    }

    /**
     * Set the event target/context
     *
     * @see    Laminas\EventManager\Event::setTarget()
     *
     * @param  StorageInterface $target
     * @return Event
     */
    public function setTarget($target)
    {
        return $this->setStorage($target);
    }

    /**
     * Alias of setTarget
     *
     * @see    Laminas\EventManager\Event::setTarget()
     *
     * @return Event
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->target = $storage;
        return $this;
    }

    /**
     * Alias of getTarget
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->getTarget();
    }
}
