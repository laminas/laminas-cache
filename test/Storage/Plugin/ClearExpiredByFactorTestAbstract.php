<?php

namespace LaminasTest\Cache\Storage\Plugin;

use ArrayObject;
use Laminas\Cache;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use LaminasTest\Cache\Storage\TestAsset\ClearExpiredMockAdapter;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;

use function array_shift;
use function count;
use function get_class;

/**
 * @covers \Laminas\Cache\Storage\Plugin\ClearExpiredByFactor
 */
class ClearExpiredByFactorTestAbstract extends AbstractCommonPluginTest
{
    use EventListenerIntrospectionTrait;

    /**
     * The storage adapter
     *
     * @var MockAdapter
     */
    protected $adapter;

    /** @var Cache\Storage\Plugin\PluginOptions */
    private $options;

    public function setUp(): void
    {
        $this->adapter = new ClearExpiredMockAdapter();
        $this->options = new Cache\Storage\Plugin\PluginOptions([
            'clearing_factor' => 1,
        ]);
        $this->plugin  = new Cache\Storage\Plugin\ClearExpiredByFactor();
        $this->plugin->setOptions($this->options);

        parent::setUp();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPluginNamesProvider()
    {
        return [
            ['clear_expired_by_factor'],
            ['clearexpiredbyfactor'],
            ['ClearExpiredByFactor'],
            ['clearExpiredByFactor'],
        ];
    }

    public function testAddPlugin(): void
    {
        $this->adapter->addPlugin($this->plugin);

        // check attached callbacks
        $expectedListeners = [
            'setItem.post'  => 'clearExpiredByFactor',
            'setItems.post' => 'clearExpiredByFactor',
            'addItem.post'  => 'clearExpiredByFactor',
            'addItems.post' => 'clearExpiredByFactor',
        ];
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->getArrayOfListenersForEvent($eventName, $this->adapter->getEventManager());

            // event should attached only once
            self::assertSame(1, count($listeners));

            // check expected callback method
            $cb = array_shift($listeners);
            self::assertArrayHasKey(0, $cb);
            self::assertSame($this->plugin, $cb[0]);
            self::assertArrayHasKey(1, $cb);
            self::assertSame($expectedCallbackMethod, $cb[1]);
        }
    }

    public function testRemovePlugin(): void
    {
        $this->adapter->addPlugin($this->plugin);
        $this->adapter->removePlugin($this->plugin);

        // no events should be attached
        self::assertEquals(0, count($this->getEventsFromEventManager($this->adapter->getEventManager())));
    }

    public function testClearExpiredByFactor(): void
    {
        $adapter = $this->getMockBuilder(get_class($this->adapter))
            ->setMethods(['clearExpired'])
            ->getMock();
        $this->options->setClearingFactor(1);

        // test clearByNamespace will be called
        $adapter
            ->expects($this->once())
            ->method('clearExpired')
            ->will($this->returnValue(true));

        // call event callback
        $result = true;
        $event  = new PostEvent('setItem.post', $adapter, new ArrayObject([
            'options' => [],
        ]), $result);
        $this->plugin->clearExpiredByFactor($event);

        self::assertTrue($event->getResult());
    }
}
