<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage;

use ArrayObject;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventManagerInterface;
use LaminasTest\Cache\Storage\TestAsset\EventsCapableStorageInterface;
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
     * @var StorageInterface
     */
    protected $adapter;

    public function setUp(): void
    {
        $this->marker  = new stdClass();
        $this->adapter = $this->createMock(StorageInterface::class);

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
        $eventManager = $this->createMock(EventManagerInterface::class);

        $adapter = $this->createMock(EventsCapableStorageInterface::class);
        $adapter
            ->expects(self::once())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $eventManager
            ->expects(self::once())
            ->method('trigger')
            ->with('capability', $adapter, self::callback(static function ($params): bool {
                self::assertInstanceOf(ArrayObject::class, $params);
                self::assertTrue(isset($params['maxTtl']));
                self::assertEquals(100, $params['maxTtl']);
                return true;
            }));

        $capabilities = new Capabilities($adapter, $this->marker);
        $capabilities->setMaxTtl($this->marker, 100);
    }
}
