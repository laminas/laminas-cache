<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Storage\Adapter\Memory as MemoryAdapter;
use Laminas\Cache\Storage\Capabilities;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Capabilities
 */
class CapabilitiesTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * Capabilities instance
     *
     * @var Capabilities
     */
    protected $_capabilities;

    /**
     * Base capabilities instance
     *
     * @var Capabilities
     */
    protected $_baseCapabilities;

    /**
     * Set/Change marker
     *
     * @var \stdClass
     */
    protected $_marker;

    /**
     * The storage adapter
     *
     * @var MemoryAdapter
     */
    protected $_adapter;
    // @codingStandardsIgnoreEnd

    public function setUp(): void
    {
        $this->_marker  = new \stdClass();
        $this->_adapter = new MemoryAdapter();

        $this->_baseCapabilities = new Capabilities($this->_adapter, $this->_marker);
        $this->_capabilities     = new Capabilities($this->_adapter, $this->_marker, [], $this->_baseCapabilities);
    }

    public function testGetAdapter(): void
    {
        self::assertSame($this->_adapter, $this->_capabilities->getAdapter());
        self::assertSame($this->_adapter, $this->_baseCapabilities->getAdapter());
    }

    public function testSetAndGetCapability(): void
    {
        $this->_capabilities->setMaxTtl($this->_marker, 100);
        self::assertEquals(100, $this->_capabilities->getMaxTtl());
    }

    public function testGetCapabilityByBaseCapabilities(): void
    {
        $this->_baseCapabilities->setMaxTtl($this->_marker, 100);
        self::assertEquals(100, $this->_capabilities->getMaxTtl());
    }

    public function testTriggerCapabilityEvent(): void
    {
        $em    = $this->_capabilities->getAdapter()->getEventManager();
        $event = null;
        $em->attach('capability', function ($eventArg) use (&$event) {
            $event = $eventArg;
        });

        $this->_capabilities->setMaxTtl($this->_marker, 100);

        self::assertInstanceOf('Laminas\EventManager\Event', $event);
        self::assertEquals('capability', $event->getName());
        self::assertSame($this->_adapter, $event->getTarget());

        $params = $event->getParams();
        self::assertInstanceOf('ArrayObject', $params);
        self::assertTrue(isset($params ['maxTtl']));
        self::assertEquals(100, $params['maxTtl']);
    }
}
