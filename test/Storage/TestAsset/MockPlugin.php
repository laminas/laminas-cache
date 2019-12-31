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
    protected $handles = array();
    protected $calledEvents = array();
    protected $eventCallbacks  = array(
        'setItem.pre'  => 'onSetItemPre',
        'setItem.post' => 'onSetItemPost'
    );

    public function __construct($options = array())
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

    public function attach(EventManagerInterface $eventCollection)
    {
        $handles = array();
        foreach ($this->eventCallbacks as $eventName => $method) {
            $handles[] = $eventCollection->attach($eventName, array($this, $method));
        }
        $this->handles[ \spl_object_hash($eventCollection) ] = $handles;
    }

    public function detach(EventManagerInterface $eventCollection)
    {
        $index = \spl_object_hash($eventCollection);
        foreach ($this->handles[$index] as $i => $handle) {
            $eventCollection->detach($handle);
            unset($this->handles[$index][$i]);
        }

        // remove empty handles of event collection
        if (!$this->handles[$index]) {
            unset($this->handles[$index]);
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
        return $this->handles;
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
