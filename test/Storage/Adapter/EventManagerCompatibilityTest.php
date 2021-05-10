<?php

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\EventManager\EventManager;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;
use PHPUnit\Framework\TestCase;

class EventManagerCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        $this->adapter = new MockAdapter();
    }

    public function testCanLazyLoadEventManager()
    {
        $events = $this->adapter->getEventManager();
        $this->assertInstanceOf(EventManager::class, $events);
        return $events;
    }

    /**
     * @depends testCanLazyLoadEventManager
     */
    public function testLazyLoadedEventManagerIsInjectedProperlyWithDefaultIdentifiers(EventManager $events)
    {
        $this->assertEquals([
            AbstractAdapter::class,
            MockAdapter::class,
        ], $events->getIdentifiers());
    }
}
