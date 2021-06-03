<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Plugin;

use ArrayObject;
use Laminas\Cache;
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Plugin\Serializer<extended>
 */
class SerializerTest extends CommonPluginTest
{
    use EventListenerIntrospectionTrait;

    // @codingStandardsIgnoreStart
    /**
     * The storage adapter
     *
     * @var \Laminas\Cache\Storage\Adapter\AbstractAdapter
     */
    protected $_adapter;
    // @codingStandardsIgnoreEnd

    protected function setUp(): void
    {
        $this->_adapter = $this->getMockForAbstractClass('Laminas\Cache\Storage\Adapter\AbstractAdapter');
        $this->_options = new Cache\Storage\Plugin\PluginOptions();
        $this->_plugin  = new Cache\Storage\Plugin\Serializer();
        $this->_plugin->setOptions($this->_options);
    }

    public function getCommonPluginNamesProvider()
    {
        return [
            ['serializer'],
            ['Serializer'],
        ];
    }

    public function testAddPlugin()
    {
        $this->_adapter->addPlugin($this->_plugin, 100);

        // check attached callbacks
        $expectedListeners = [
            'getItem.post'        => 'onReadItemPost',
            'getItems.post'       => 'onReadItemsPost',

            'setItem.pre'         => 'onWriteItemPre',
            'setItems.pre'        => 'onWriteItemsPre',
            'addItem.pre'         => 'onWriteItemPre',
            'addItems.pre'        => 'onWriteItemsPre',
            'replaceItem.pre'     => 'onWriteItemPre',
            'replaceItems.pre'    => 'onWriteItemsPre',
            'checkAndSetItem.pre' => 'onWriteItemPre',

            'incrementItem.pre'   => 'onIncrementItemPre',
            'incrementItems.pre'  => 'onIncrementItemsPre',
            'decrementItem.pre'   => 'onDecrementItemPre',
            'decrementItems.pre'  => 'onDecrementItemsPre',

            'getCapabilities.post' => 'onGetCapabilitiesPost',
        ];

        $events = $this->_adapter->getEventManager();
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->getArrayOfListenersForEvent($eventName, $events);

            // event should attached only once
            $this->assertSame(1, count($listeners));

            // check expected callback method
            $cb = array_shift($listeners);
            $this->assertArrayHasKey(0, $cb);
            $this->assertSame($this->_plugin, $cb[0]);
            $this->assertArrayHasKey(1, $cb);
            $this->assertSame($expectedCallbackMethod, $cb[1]);

            // check expected priority
            if (substr($eventName, -4) == '.pre') {
                $this->assertListenerAtPriority($cb, 100, $eventName, $events);
            } else {
                $this->assertListenerAtPriority($cb, -100, $eventName, $events);
            }
        }
    }

    public function testRemovePlugin()
    {
        $this->_adapter->addPlugin($this->_plugin);
        $this->_adapter->removePlugin($this->_plugin);

        // no events should be attached
        $this->assertEquals(0, count($this->getEventsFromEventManager($this->_adapter->getEventManager())));
    }

    public function testUnserializeOnReadItem()
    {
        $args  = new ArrayObject([
            'key'      => 'test',
            'success'  => true,
            'casToken' => null,
        ]);
        $value = serialize(123);
        $event = new PostEvent('getItem.post', $this->_adapter, $args, $value);
        $this->_plugin->onReadItemPost($event);

        $this->assertFalse($event->propagationIsStopped(), 'Event propagation has been stopped');
        $this->assertSame(123, $event->getResult(), 'Result was not unserialized');
    }

    public function testDontUnserializeOnReadMissingItem()
    {
        $args  = new ArrayObject(['key' => 'test']);
        $value = null;
        $event = new PostEvent('getItem.post', $this->_adapter, $args, $value);
        $this->_plugin->onReadItemPost($event);

        $this->assertFalse($event->propagationIsStopped(), 'Event propagation has been stopped');
        $this->assertSame($value, $event->getResult(), 'Missing item was unserialized');
    }

    public function testUnserializeOnReadItems()
    {
        $values = ['key1' => serialize(123), 'key2' => serialize(456)];
        $args   = new ArrayObject(['keys' => array_keys($values) + ['missing']]);
        $event  = new PostEvent('getItems.post', $this->_adapter, $args, $values);

        $this->_plugin->onReadItemsPost($event);

        $this->assertFalse($event->propagationIsStopped(), 'Event propagation has been stopped');

        $values = $event->getResult();
        $this->assertSame(123, $values['key1'], "Item 'key1' was not unserialized");
        $this->assertSame(456, $values['key2'], "Item 'key2' was not unserialized");
        $this->assertArrayNotHasKey('missing', $values, 'Missing item should not be present in the result');
    }

    public function testOnDecrementItemPreWillDecrementValue(): void
    {
        $adapter = $this->createMock(Cache\Storage\StorageInterface::class);
        $adapter
            ->expects(self::once())
            ->method('getItem')
            ->willReturnCallback(static function (string $key, &$success, &$casToken): int {
                $success = true;
                $casToken = 10;

                return $casToken;
            });

        $adapter
            ->expects(self::once())
            ->method('checkAndSetItem')
            ->with(10, 'foo', 5)
            ->willReturn(true);

        $event = new Event('', $adapter, new ArrayObject([
            'key' => 'foo',
            'value' => 5,
        ]));

        $this->_plugin->onDecrementItemPre($event);
    }
}
