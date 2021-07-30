<?php

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Storage\Adapter\Memory as MemoryAdapter;
use Laminas\Cache\Storage\Capabilities;
use Laminas\EventManager\Event;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Capabilities
 */
class CapabilitiesTest extends TestCase
{
    /**
     * Capabilities instance
     *
     * @var Capabilities
     */
    protected $capabilities;

    /**
     * Base capabilities instance
     *
     * @var Capabilities
     */
    protected $baseCapabilities;

    /**
     * Set/Change marker
     *
     * @var stdClass
     */
    protected $marker;

    /**
     * The storage adapter
     *
     * @var MemoryAdapter
     */
    protected $adapter;

    public function setUp(): void
    {
        $this->marker  = new stdClass();
        $this->adapter = new MemoryAdapter();

        $this->baseCapabilities = new Capabilities($this->adapter, $this->marker);
        $this->capabilities     = new Capabilities($this->adapter, $this->marker, [], $this->baseCapabilities);
    }

    public function testGetAdapter(): void
    {
        self::assertSame($this->adapter, $this->capabilities->getAdapter());
        self::assertSame($this->adapter, $this->baseCapabilities->getAdapter());
    }

    public function testSetAndGetCapability(): void
    {
        $this->capabilities->setMaxTtl($this->marker, 100);
        self::assertEquals(100, $this->capabilities->getMaxTtl());
    }

    public function testGetCapabilityByBaseCapabilities(): void
    {
        $this->baseCapabilities->setMaxTtl($this->marker, 100);
        self::assertEquals(100, $this->capabilities->getMaxTtl());
    }

    public function testTriggerCapabilityEvent(): void
    {
        $em    = $this->capabilities->getAdapter()->getEventManager();
        $event = null;
        $em->attach('capability', function ($eventArg) use (&$event) {
            $event = $eventArg;
        });

        $this->capabilities->setMaxTtl($this->marker, 100);

        self::assertInstanceOf(Event::class, $event);
        self::assertEquals('capability', $event->getName());
        self::assertSame($this->adapter, $event->getTarget());

        $params = $event->getParams();
        self::assertInstanceOf('ArrayObject', $params);
        self::assertTrue(isset($params ['maxTtl']));
        self::assertEquals(100, $params['maxTtl']);
    }
}
