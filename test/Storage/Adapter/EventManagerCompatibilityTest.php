<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\EventManager\EventManager;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;
use PHPUnit\Framework\TestCase;

class EventManagerCompatibilityTest extends TestCase
{
    private MockAdapter $adapter;

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
