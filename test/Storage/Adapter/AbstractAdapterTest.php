<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use ArrayObject;
use Laminas\Cache;
use Laminas\Cache\Exception;
use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Exception\RuntimeException;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\ResponseCollection;
use LaminasTest\Cache\Storage\TestAsset\MockPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

use function array_keys;
use function array_merge;
use function array_unique;
use function assert;
use function call_user_func_array;
use function count;
use function current;
use function serialize;
use function ucfirst;

final class AbstractAdapterTest extends TestCase
{
    private ?AdapterOptions $options;

    public function setUp(): void
    {
        $this->options = new AdapterOptions();
    }

    public function testGetOptions(): void
    {
        $storage = $this->getMockForAbstractAdapter();

        $options = $storage->getOptions();
        self::assertInstanceOf(AdapterOptions::class, $options);
        self::assertIsBool($options->getWritable());
        self::assertIsBool($options->getReadable());
        self::assertIsInt($options->getTtl());
        self::assertIsString($options->getNamespace());
        self::assertIsString($options->getKeyPattern());
    }

    public function testSetWritable(): void
    {
        $this->options->setWritable(true);
        self::assertTrue($this->options->getWritable());

        $this->options->setWritable(false);
        self::assertFalse($this->options->getWritable());
    }

    public function testSetReadable(): void
    {
        $this->options->setReadable(true);
        self::assertTrue($this->options->getReadable());

        $this->options->setReadable(false);
        self::assertFalse($this->options->getReadable());
    }

    public function testSetTtl(): void
    {
        $this->options->setTtl('123');
        self::assertSame(123, $this->options->getTtl());
    }

    public function testSetTtlThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->options->setTtl(-1);
    }

    public function testGetDefaultNamespaceNotEmpty(): void
    {
        $ns = $this->options->getNamespace();
        self::assertNotEmpty($ns);
    }

    public function testSetNamespace(): void
    {
        $this->options->setNamespace('new_namespace');
        self::assertSame('new_namespace', $this->options->getNamespace());
    }

    public function testSetNamespace0(): void
    {
        $this->options->setNamespace('0');
        self::assertSame('0', $this->options->getNamespace());
    }

    public function testSetKeyPattern(): void
    {
        $this->options->setKeyPattern('/^[key]+$/Di');
        self::assertEquals('/^[key]+$/Di', $this->options->getKeyPattern());
    }

    public function testUnsetKeyPattern(): void
    {
        $this->options->setKeyPattern(null);
        self::assertSame('', $this->options->getKeyPattern());
    }

    public function testSetKeyPatternThrowsExceptionOnInvalidPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->options->setKeyPattern('#');
    }

    public function testPluginRegistry(): void
    {
        $storage = $this->getMockForAbstractAdapter();

        $plugin = new MockPlugin();

        // no plugin registered
        self::assertFalse($storage->hasPlugin($plugin));
        self::assertCount(0, $storage->getPluginRegistry());
        self::assertCount(0, $plugin->getHandles());

        // register a plugin
        self::assertSame($storage, $storage->addPlugin($plugin));
        self::assertTrue($storage->hasPlugin($plugin));
        self::assertCount(1, $storage->getPluginRegistry());

        // test registered callback handles
        $handles = $plugin->getHandles();
        self::assertCount(2, $handles);

        // test unregister a plugin
        self::assertSame($storage, $storage->removePlugin($plugin));
        self::assertFalse($storage->hasPlugin($plugin));
        self::assertCount(0, $storage->getPluginRegistry());
        self::assertCount(0, $plugin->getHandles());
    }

    public function testInternalTriggerPre(): void
    {
        $storage = $this->getMockForAbstractAdapter();

        $plugin = new MockPlugin();
        $storage->addPlugin($plugin);

        $params = new ArrayObject([
            'key'   => 'key1',
            'value' => 'value1',
        ]);

        // call protected method
        $method       = new ReflectionMethod($storage::class, 'triggerPre');
        $rsCollection = $method->invoke($storage, 'setItem', $params);
        self::assertInstanceOf(ResponseCollection::class, $rsCollection);

        // test called event
        $calledEvents = $plugin->getCalledEvents();
        self::assertCount(1, $calledEvents);

        $event = current($calledEvents);
        self::assertInstanceOf(Event::class, $event);
        self::assertEquals('setItem.pre', $event->getName());
        self::assertSame($storage, $event->getTarget());
        self::assertSame($params, $event->getParams());
    }

    public function testInternalTriggerPost(): void
    {
        $storage = $this->getMockForAbstractAdapter();

        $plugin = new MockPlugin();
        $storage->addPlugin($plugin);

        $params = new ArrayObject([
            'key'   => 'key1',
            'value' => 'value1',
        ]);
        $result = true;

        // call protected method
        $method = new ReflectionMethod($storage::class, 'triggerPost');
        $result = $method->invokeArgs($storage, ['setItem', $params, &$result]);

        // test called event
        $calledEvents = $plugin->getCalledEvents();
        self::assertCount(1, $calledEvents);
        $event = current($calledEvents);

        // return value of triggerPost and the called event should be the same
        self::assertSame($result, $event->getResult());

        self::assertInstanceOf(PostEvent::class, $event);
        self::assertEquals('setItem.post', $event->getName());
        self::assertSame($storage, $event->getTarget());
        self::assertSame($params, $event->getParams());
        self::assertSame($result, $event->getResult());
    }

    public function testInternalTriggerExceptionThrowRuntimeException(): void
    {
        $storage = $this->getMockForAbstractAdapter();

        $plugin = new MockPlugin();
        $storage->addPlugin($plugin);

        $result = null;
        $params = new ArrayObject([
            'key'   => 'key1',
            'value' => 'value1',
        ]);

        // call protected method
        $method = new ReflectionMethod($storage::class, 'triggerException');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');
        $method->invokeArgs($storage, ['setItem', $params, &$result, new Exception\RuntimeException('test')]);
    }

    public function testGetItemCallsInternalGetItem(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalGetItem']);

        $key    = 'key1';
        $result = 'value1';

        $storage
            ->expects($this->once())
            ->method('internalGetItem')
            ->with($this->equalTo($key))
            ->willReturn($result);

        $rs = $storage->getItem($key);
        self::assertEquals($result, $rs);
    }

    public function testGetItemsCallsInternalGetItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalGetItems']);

        $keys   = ['key1', 'key2'];
        $result = ['key2' => 'value2'];

        $storage
            ->expects($this->once())
            ->method('internalGetItems')
            ->with($this->equalTo($keys))
            ->willReturn($result);

        $rs = $storage->getItems($keys);
        self::assertEquals($result, $rs);
    }

    public function testInternalGetItemsCallsInternalGetItemForEachKey(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalGetItem']);

        $items  = ['key1' => 'value1', 'keyNotFound' => false, 'key2' => 'value2'];
        $result = ['key1' => 'value1', 'key2' => 'value2'];

        for ($i = 0, $iMax = count($items); $i <= $iMax; $i++) {
            $storage
                ->method('internalGetItem')
                ->with(
                    $this->stringContains('key'),
                    $this->anything()
                )
                ->willReturnCallback(static function (string $key, ?bool &$success) use ($items): ?string {
                    if ($items[$key]) {
                        $success = true;

                        return $items[$key];
                    }
                    $success = false;
                    return null;
                });
        }

        self::assertSame($result, $storage->getItems(array_keys($items)));
    }

    public function testHasItemCallsInternalHasItem(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItem']);

        $key    = 'key1';
        $result = true;

        $storage
            ->expects($this->once())
            ->method('internalHasItem')
            ->with($this->equalTo($key))
            ->will($this->returnValue($result));

        $rs = $storage->hasItem($key);
        self::assertSame($result, $rs);
    }

    public function testHasItemsCallsInternalHasItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItems']);

        $keys   = ['key1', 'key2'];
        $result = ['key2'];

        $storage
            ->expects($this->once())
            ->method('internalHasItems')
            ->with($this->equalTo($keys))
            ->will($this->returnValue($result));

        $rs = $storage->hasItems($keys);
        self::assertEquals($result, $rs);
    }

    public function testInternalHasItemsCallsInternalHasItem(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItem']);

        $items = ['key1' => true];

        $storage
            ->expects($this->atLeastOnce())
            ->method('internalHasItem')
            ->with($this->equalTo('key1'))
            ->willReturn(true);

        $rs = $storage->hasItems(array_keys($items));
        self::assertEquals(['key1'], $rs);
    }

    public function testGetItemReturnsNullIfFailed(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalGetItem']);

        $key = 'key1';

        // Do not throw exceptions outside the adapter
        $pluginOptions = new PluginOptions(
            ['throw_exceptions' => false]
        );
        $plugin        = new Cache\Storage\Plugin\ExceptionHandler();
        $plugin->setOptions($pluginOptions);
        $storage->addPlugin($plugin);

        // Simulate internalGetItem() throwing an exception
        $storage
            ->expects($this->once())
            ->method('internalGetItem')
            ->with($this->equalTo($key))
            ->willThrowException(new \Exception('internalGetItem failed'));

        $result = $storage->getItem($key, $success);
        self::assertNull($result, 'GetItem should return null the item cannot be retrieved');
        self::assertFalse($success, '$success should be false if the item cannot be retrieved');
    }

    public function simpleEventHandlingMethodDefinitions(): array
    {
        $capabilities = new Capabilities($this->getMockForAbstractAdapter(), new stdClass());

        return [
            //    name, internalName, args, returnValue
            ['hasItem', 'internalGetItem', ['k'], 'v'],
            ['hasItems', 'internalHasItems', [['k1', 'k2']], ['v1', 'v2']],
            ['getItem', 'internalGetItem', ['k'], 'v'],
            ['getItems', 'internalGetItems', [['k1', 'k2']], ['k1' => 'v1', 'k2' => 'v2']],
            ['setItem', 'internalSetItem', ['k', 'v'], true],
            ['setItems', 'internalSetItems', [['k1' => 'v1', 'k2' => 'v2']], []],
            ['replaceItem', 'internalReplaceItem', ['k', 'v'], true],
            ['replaceItems', 'internalReplaceItems', [['k1' => 'v1', 'k2' => 'v2']], []],
            ['addItem', 'internalAddItem', ['k', 'v'], true],
            ['addItems', 'internalAddItems', [['k1' => 'v1', 'k2' => 'v2']], []],
            ['checkAndSetItem', 'internalCheckAndSetItem', [123, 'k', 'v'], true],
            ['touchItem', 'internalTouchItem', ['k'], true],
            ['touchItems', 'internalTouchItems', [['k1', 'k2']], []],
            ['removeItem', 'internalRemoveItem', ['k'], true],
            ['removeItems', 'internalRemoveItems', [['k1', 'k2']], []],
            ['getCapabilities', 'internalGetCapabilities', [], $capabilities],
        ];
    }

    public function testSetItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['setItem', 'internalSetItem']);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // foreach item call 'internalSetItem' instead of 'setItem'
        $storage->expects($this->never())->method('setItem');
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->willReturn(true);

        self::assertSame([], $storage->setItems($items));
    }

    public function testSetItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalSetItem']);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // return false to indicate that the operation failed
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->willReturn(false);

        self::assertSame(array_keys($items), $storage->setItems($items));
    }

    public function testAddItems(): void
    {
        $storage = $this->getMockForAbstractAdapter([
            'getItem',
            'internalGetItem',
            'hasItem',
            'internalHasItem',
            'setItem',
            'internalSetItem',
        ]);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // first check if the items already exists using has
        // call 'internalHasItem' instead of 'hasItem' or '[internal]GetItem'
        $storage->expects($this->never())->method('hasItem');
        $storage->expects($this->never())->method('getItem');
        $storage->expects($this->never())->method('internalGetItem');
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->willReturn(false);

        // If not create the items using set
        // call 'internalSetItem' instead of 'setItem'
        $storage->expects($this->never())->method('setItem');
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->willReturn(true);

        self::assertSame([], $storage->addItems($items));
    }

    public function testAddItemsExists(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItem', 'internalSetItem']);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // first check if items already exists
        // -> return true to indicate that the item already exist
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->willReturn(true);

        // set item should never be called
        $storage->expects($this->never())->method('internalSetItem');

        self::assertSame(array_keys($items), $storage->addItems($items));
    }

    public function testAddItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItem', 'internalSetItem']);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // first check if items already exists
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->willReturn(false);

        // if not create the items
        // -> return false to indicate creation failed
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->willReturn(false);

        self::assertSame(array_keys($items), $storage->addItems($items));
    }

    public function testReplaceItems(): void
    {
        $storage = $this->getMockForAbstractAdapter([
            'hasItem',
            'internalHasItem',
            'getItem',
            'internalGetItem',
            'setItem',
            'internalSetItem',
        ]);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // First check if the item already exists using has
        // call 'internalHasItem' instead of 'hasItem' or '[internal]GetItem'
        $storage->expects($this->never())->method('hasItem');
        $storage->expects($this->never())->method('getItem');
        $storage->expects($this->never())->method('internalGetItem');
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->willReturn(true);

        // if yes overwrite the items
        // call 'internalSetItem' instead of 'setItem'
        $storage->expects($this->never())->method('setItem');
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->willReturn(true);

        self::assertSame([], $storage->replaceItems($items));
    }

    public function testReplaceItemsMissing(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItem', 'internalSetItem']);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // First check if the items already exists
        // -> return false to indicate the items doesn't exists
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->willReturn(false);

        // writing items should never be called
        $storage->expects($this->never())->method('internalSetItem');

        self::assertSame(array_keys($items), $storage->replaceItems($items));
    }

    public function testReplaceItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItem', 'internalSetItem']);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // First check if the items already exists
        // -> return true to indicate the items exists
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->willReturn(true);

        // if yes overwrite the items
        // -> return false to indicate that overwriting failed
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->willReturn(false);

        self::assertSame(array_keys($items), $storage->replaceItems($items));
    }

    public function testRemoveItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['removeItem', 'internalRemoveItem']);

        $keys = ['key1', 'key2'];

        // call 'internalRemoveItem' instaed of 'removeItem'
        $storage->expects($this->never())->method('removeItem');
        $storage
            ->expects($this->exactly(count($keys)))
            ->method('internalRemoveItem')
            ->with($this->stringContains('key'))
            ->willReturn(true);

        self::assertSame([], $storage->removeItems($keys));
    }

    public function testRemoveItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalRemoveItem']);

        $keys = ['key1', 'key2', 'key3'];

        // call 'internalRemoveItem'
        // -> return false to indicate that no item was removed
        $storage
            ->expects($this->exactly(count($keys)))
            ->method('internalRemoveItem')
            ->with($this->stringContains('key'))
            ->willReturn(false);

        self::assertSame($keys, $storage->removeItems($keys));
    }

    public function testTouchItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['touchItem', 'internalTouchItem']);

        $items = ['key1', 'key2'];

        // foreach item call 'internalTouchItem' instead of 'touchItem'
        $storage->expects($this->never())->method('touchItem');
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalTouchItem')
            ->with($this->stringContains('key'))
            ->willReturn(true);

        self::assertSame([], $storage->touchItems($items));
    }

    public function testTouchItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalTouchItem']);

        $items = ['key1', 'key2'];

        // return false to indicate that the operation failed
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalTouchItem')
            ->with($this->stringContains('key'))
            ->willReturn(false);

        self::assertSame($items, $storage->touchItems($items));
    }

    public function testPreEventsCanChangeArguments(): void
    {
        // getItem(s)
        $this->checkPreEventCanChangeArguments('getItem', [
            'key' => 'key',
        ], [
            'key' => 'changedKey',
        ]);

        $this->checkPreEventCanChangeArguments('getItems', [
            'keys' => ['key'],
        ], [
            'keys' => ['changedKey'],
        ]);

        // hasItem(s)
        $this->checkPreEventCanChangeArguments('hasItem', [
            'key' => 'key',
        ], [
            'key' => 'changedKey',
        ]);

        $this->checkPreEventCanChangeArguments('hasItems', [
            'keys' => ['key'],
        ], [
            'keys' => ['changedKey'],
        ]);

        // setItem(s)
        $this->checkPreEventCanChangeArguments('setItem', [
            'key'   => 'key',
            'value' => 'value',
        ], [
            'key'   => 'changedKey',
            'value' => 'changedValue',
        ]);

        $this->checkPreEventCanChangeArguments('setItems', [
            'keyValuePairs' => ['key' => 'value'],
        ], [
            'keyValuePairs' => ['changedKey' => 'changedValue'],
        ]);

        // addItem(s)
        $this->checkPreEventCanChangeArguments('addItem', [
            'key'   => 'key',
            'value' => 'value',
        ], [
            'key'   => 'changedKey',
            'value' => 'changedValue',
        ]);

        $this->checkPreEventCanChangeArguments('addItems', [
            'keyValuePairs' => ['key' => 'value'],
        ], [
            'keyValuePairs' => ['changedKey' => 'changedValue'],
        ]);

        // replaceItem(s)
        $this->checkPreEventCanChangeArguments('replaceItem', [
            'key'   => 'key',
            'value' => 'value',
        ], [
            'key'   => 'changedKey',
            'value' => 'changedValue',
        ]);

        $this->checkPreEventCanChangeArguments('replaceItems', [
            'keyValuePairs' => ['key' => 'value'],
        ], [
            'keyValuePairs' => ['changedKey' => 'changedValue'],
        ]);

        // CAS
        $this->checkPreEventCanChangeArguments('checkAndSetItem', [
            'token' => 'token',
            'key'   => 'key',
            'value' => 'value',
        ], [
            'token' => 'changedToken',
            'key'   => 'changedKey',
            'value' => 'changedValue',
        ]);

        // touchItem(s)
        $this->checkPreEventCanChangeArguments('touchItem', [
            'key' => 'key',
        ], [
            'key' => 'changedKey',
        ]);

        $this->checkPreEventCanChangeArguments('touchItems', [
            'keys' => ['key'],
        ], [
            'keys' => ['changedKey'],
        ]);

        // removeItem(s)
        $this->checkPreEventCanChangeArguments('removeItem', [
            'key' => 'key',
        ], [
            'key' => 'changedKey',
        ]);

        $this->checkPreEventCanChangeArguments('removeItems', [
            'keys' => ['key'],
        ], [
            'keys' => ['changedKey'],
        ]);
    }

    protected function checkPreEventCanChangeArguments(string $method, array $args, array $expectedArgs): void
    {
        $internalMethod = 'internal' . ucfirst($method);
        $eventName      = $method . '.pre';

        // init mock
        $storage = $this->getMockForAbstractAdapter([$internalMethod]);
        $storage->getEventManager()->attach($eventName, static function (Event $event) use ($expectedArgs): void {
            $params = $event->getParams();
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            assert($params instanceof ArrayObject);
            $params->exchangeArray(array_merge($params->getArrayCopy(), $expectedArgs));
        });

        // set expected arguments of internal method call
        $tmp    = $storage->expects($this->once())->method($internalMethod);
        $equals = [];
        foreach ($expectedArgs as $v) {
            $equals[] = $this->equalTo($v);
        }
        call_user_func_array([$tmp, 'with'], $equals);

        // run
        call_user_func_array([$storage, $method], $args);
    }

    /**
     * Generates a mock of the abstract storage adapter by mocking all abstract and the given methods
     * Also sets the adapter options
     *
     * @psalm-param list<non-empty-string> $methods
     */
    protected function getMockForAbstractAdapter(array $methods = []): MockObject&AbstractAdapter
    {
        if (! $methods) {
            $adapter = $this->getMockForAbstractClass(AbstractAdapter::class);
        } else {
            $reflection = new ReflectionClass(AbstractAdapter::class);
            foreach ($reflection->getMethods() as $method) {
                if ($method->isAbstract()) {
                    $methods[] = $method->getName();
                }
            }
            $adapter = $this->getMockBuilder(AbstractAdapter::class)
                ->onlyMethods(array_unique($methods))
                ->disableArgumentCloning()
                ->getMock();
        }

        $adapter->setOptions($this->options ?? new AdapterOptions());

        return $adapter;
    }

    public function testCanCompareOldValueWithTokenWhenUsedWithSerializerPlugin(): void
    {
        $storage = $this->getMockForAbstractAdapter();
        $storage
            ->addPlugin(new Serializer());
        $storage
            ->expects(self::once())
            ->method('internalSetItem')
            ->with('foo', serialize('baz'))
            ->willReturn(true);

        $storage
            ->expects(self::once())
            ->method('internalGetItem')
            ->with('foo')
            ->willReturn(serialize('bar'));

        self::assertTrue($storage->checkAndSetItem('bar', 'foo', 'baz'));
    }
}
