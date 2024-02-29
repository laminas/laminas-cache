<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\Plugin;
use Laminas\Cache\Storage\Plugin\AbstractPlugin;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerInterface;

class MockPlugin extends AbstractPlugin
{
    /** @var array<callable> */
    protected array $handles = [];

    /** @var array<int,Event> */
    protected array $calledEvents = [];

    /** @var array<string,string> */
    protected array $eventCallbacks = [
        'setItem.pre'  => 'onSetItemPre',
        'setItem.post' => 'onSetItemPost',
    ];

    public function __construct(array $options = [])
    {
        $options = new Plugin\PluginOptions($options);
        if ($options instanceof Plugin\PluginOptions) {
            $this->setOptions($options);
        }
    }

    public function setOptions(Plugin\PluginOptions $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        foreach ($this->eventCallbacks as $eventName => $method) {
            $this->listeners[] = $events->attach($eventName, [$this, $method], $priority);
        }
    }

    public function onSetItemPre(Event $event): void
    {
        $this->calledEvents[] = $event;
    }

    public function onSetItemPost(Event $event): void
    {
        $this->calledEvents[] = $event;
    }

    public function getHandles(): array
    {
        return $this->listeners;
    }

    public function getEventCallbacks(): array
    {
        return $this->eventCallbacks;
    }

    public function getCalledEvents(): array
    {
        return $this->calledEvents;
    }
}
