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
    /**
     * @var MockAdapter
     */
    private $adapter;

    public function setUp(): void
    {
        $this->adapter = new MockAdapter();
    }

    public function testLazyLoadedEventManagerIsInjectedProperlyWithDefaultIdentifiers(): void
    {
        $events = $this->adapter->getEventManager();
        self::assertInstanceOf(EventManager::class, $events);

        self::assertEquals([
            AbstractAdapter::class,
            MockAdapter::class,
        ], $events->getIdentifiers());
    }
}
