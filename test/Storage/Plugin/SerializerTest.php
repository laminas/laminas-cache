<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Plugin;

use ArrayObject;
use Laminas\Cache;
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\Storage\PostEvent;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Serializer\AdapterPluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;

use function array_keys;
use function array_shift;
use function serialize;
use function str_ends_with;

final class SerializerTest extends AbstractCommonPluginTest
{
    use EventListenerIntrospectionTrait;

    private MockAdapter $adapter;

    private PluginOptions $options;

    protected function setUp(): void
    {
        $this->adapter = new MockAdapter();
        $this->options = new Cache\Storage\Plugin\PluginOptions();
        $this->plugin  = new Serializer(new AdapterPluginManager(new ServiceManager()));
        $this->plugin->setOptions($this->options);
    }

    public function getCommonPluginNamesProvider(): array
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
            'getCapabilities.post' => 'onGetCapabilitiesPost',
        ];

        $events = $this->adapter->getEventManager();
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->getArrayOfListenersForEvent($eventName, $events);

            // event should attached only once
            self::assertCount(1, $listeners);

            // check expected callback method
            $cb = array_shift($listeners);
            self::assertArrayHasKey(0, $cb);
            self::assertSame($this->plugin, $cb[0]);
            self::assertArrayHasKey(1, $cb);
            self::assertSame($expectedCallbackMethod, $cb[1]);

            // check expected priority
            if (str_ends_with($eventName, '.pre')) {
                self::assertListenerAtPriority($cb, 100, $eventName, $events);
            } else {
                self::assertListenerAtPriority($cb, -100, $eventName, $events);
            }
        }
    }

    public function testRemovePlugin(): void
    {
        $this->adapter->addPlugin($this->plugin);
        $this->adapter->removePlugin($this->plugin);

        // no events should be attached
        self::assertCount(0, $this->getEventsFromEventManager($this->adapter->getEventManager()));
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

    public function testonWriteItemPreSerializesTokenOncePassed(): void
    {
        $plugin     = new Serializer(new AdapterPluginManager(new ServiceManager()));
        $parameters = new ArrayObject([
            'key'   => 'foo',
            'value' => 10,
            'token' => 10,
        ]);
        $event      = new Event('foo', $this->createMock(StorageInterface::class), $parameters);

        $plugin->onWriteItemPre($event);

        self::assertSame($parameters['value'], $parameters['token']);
    }
}
