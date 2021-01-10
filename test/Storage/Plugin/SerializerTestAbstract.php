<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Plugin;

use ArrayObject;
use Laminas\Cache;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;

use function array_keys;
use function array_shift;
use function count;
use function serialize;
use function substr;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Plugin\Serializer<extended>
 */
class SerializerTestAbstract extends AbstractCommonPluginTest
{
    use EventListenerIntrospectionTrait;

    /**
     * The storage adapter
     *
     * @var AbstractAdapter
     */
    protected $adapter;

    /** @var Cache\Storage\Plugin\PluginOptions */
    private $options;

    public function setUp(): void
    {
        $this->adapter = $this->getMockForAbstractClass(AbstractAdapter::class);
        $this->options = new Cache\Storage\Plugin\PluginOptions();
        $this->plugin  = new Cache\Storage\Plugin\Serializer();
        $this->plugin->setOptions($this->options);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPluginNamesProvider()
    {
        return [
            'lowercase' => ['serializer'],
            'ucfirst'   => ['Serializer'],
        ];
    }

    public function testAddPlugin(): void
    {
        $this->adapter->addPlugin($this->plugin, 100);

        // check attached callbacks
        $expectedListeners = [
            'getItem.post'         => 'onReadItemPost',
            'getItems.post'        => 'onReadItemsPost',
            'setItem.pre'          => 'onWriteItemPre',
            'setItems.pre'         => 'onWriteItemsPre',
            'addItem.pre'          => 'onWriteItemPre',
            'addItems.pre'         => 'onWriteItemsPre',
            'replaceItem.pre'      => 'onWriteItemPre',
            'replaceItems.pre'     => 'onWriteItemsPre',
            'checkAndSetItem.pre'  => 'onWriteItemPre',
            'incrementItem.pre'    => 'onIncrementItemPre',
            'incrementItems.pre'   => 'onIncrementItemsPre',
            'decrementItem.pre'    => 'onDecrementItemPre',
            'decrementItems.pre'   => 'onDecrementItemsPre',
            'getCapabilities.post' => 'onGetCapabilitiesPost',
        ];

        $events = $this->adapter->getEventManager();
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->getArrayOfListenersForEvent($eventName, $events);

            // event should attached only once
            self::assertSame(1, count($listeners));

            // check expected callback method
            $cb = array_shift($listeners);
            self::assertArrayHasKey(0, $cb);
            self::assertSame($this->plugin, $cb[0]);
            self::assertArrayHasKey(1, $cb);
            self::assertSame($expectedCallbackMethod, $cb[1]);

            $expectedPriority = -100;

            // check expected priority
            if (substr($eventName, -4) === '.pre') {
                $expectedPriority = 100;
            }

            $this->assertListenerAtPriority(
                $cb,
                $expectedPriority,
                $eventName,
                $events
            );
        }
    }

    public function testRemovePlugin(): void
    {
        $this->adapter->addPlugin($this->plugin);
        $this->adapter->removePlugin($this->plugin);

        // no events should be attached
        self::assertEquals(0, count($this->getEventsFromEventManager($this->adapter->getEventManager())));
    }

    public function testUnserializeOnReadItem(): void
    {
        $args  = new ArrayObject([
            'key'      => 'test',
            'success'  => true,
            'casToken' => null,
        ]);
        $value = serialize(123);
        $event = new PostEvent('getItem.post', $this->adapter, $args, $value);
        $this->plugin->onReadItemPost($event);

        self::assertFalse($event->propagationIsStopped(), 'Event propagation has been stopped');
        self::assertSame(123, $event->getResult(), 'Result was not unserialized');
    }

    public function testDontUnserializeOnReadMissingItem(): void
    {
        $args  = new ArrayObject(['key' => 'test']);
        $value = null;
        $event = new PostEvent('getItem.post', $this->adapter, $args, $value);
        $this->plugin->onReadItemPost($event);

        self::assertFalse($event->propagationIsStopped(), 'Event propagation has been stopped');
        self::assertSame($value, $event->getResult(), 'Missing item was unserialized');
    }

    public function testUnserializeOnReadItems(): void
    {
        $values = ['key1' => serialize(123), 'key2' => serialize(456)];
        $args   = new ArrayObject(['keys' => array_keys($values) + ['missing']]);
        $event  = new PostEvent('getItems.post', $this->adapter, $args, $values);

        $this->plugin->onReadItemsPost($event);

        self::assertFalse($event->propagationIsStopped(), 'Event propagation has been stopped');

        $values = $event->getResult();
        self::assertSame(123, $values['key1'], "Item 'key1' was not unserialized");
        self::assertSame(456, $values['key2'], "Item 'key2' was not unserialized");
        self::assertArrayNotHasKey('missing', $values, 'Missing item should not be present in the result');
    }
}
