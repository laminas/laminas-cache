<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\EventManager\EventManager;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;
use PHPUnit\Framework\TestCase;

class EventManagerCompatibilityTest extends TestCase
{
    public function setUp()
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
