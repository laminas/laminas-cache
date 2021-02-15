<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\Plugin;
use Laminas\Cache\Storage\Plugin\AbstractPlugin;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\Event;

class MockPlugin extends AbstractPlugin
{

    protected $options;
    protected $handles = [];
    protected $calledEvents = [];
    protected $eventCallbacks  = [
        'setItem.pre'  => 'onSetItemPre',
        'setItem.post' => 'onSetItemPost'
    ];

    public function __construct($options = [])
    {
        if (is_array($options)) {
            $options = new Plugin\PluginOptions($options);
        }
        if ($options instanceof Plugin\PluginOptions) {
            $this->setOptions($options);
        }
    }

    public function setOptions(Plugin\PluginOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function attach(EventManagerInterface $eventCollection, $priority = 1)
    {
        foreach ($this->eventCallbacks as $eventName => $method) {
            $this->listeners[] = $eventCollection->attach($eventName, [$this, $method], $priority);
        }
    }

    public function onSetItemPre(Event $event)
    {
        $this->calledEvents[] = $event;
    }

    public function onSetItemPost(Event $event)
    {
        $this->calledEvents[] = $event;
    }

    public function getHandles()
    {
        return $this->listeners;
    }

    public function getEventCallbacks()
    {
        return $this->eventCallbacks;
    }

    public function getCalledEvents()
    {
        return $this->calledEvents;
    }
}
