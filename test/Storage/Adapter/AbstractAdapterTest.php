<?php

namespace LaminasTest\Cache\Storage\Adapter;

use ArrayObject;
use Laminas\Cache\Exception;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\ExceptionEvent;
use Laminas\Cache\Storage\Plugin\ExceptionHandler;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\ResponseCollection;
use LaminasTest\Cache\Storage\TestAsset\MockPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

use function array_keys;
use function array_map;
use function array_unique;
use function assert;
use function call_user_func_array;
use function count;
use function current;
use function get_class;
use function is_array;
use function is_callable;
use function sprintf;
use function ucfirst;

/**
 * @group      \Laminas_Cache
 * @covers \Laminas\Cache\Storage\Adapter\AdapterOptions<extended>
 */
final class AbstractAdapterTest extends TestCase
{
    /**
     * Adapter options
     *
     * @var AdapterOptions|null
     */
    protected $options;

    public function setUp(): void
    {
        $this->options = new AdapterOptions();
    }

    public function testGetOptions(): void
    {
        $storage = $this->getMockForAbstractAdapter();

        $options = $storage->getOptions();
        $this->assertInstanceOf(AdapterOptions::class, $options);
        $this->assertIsBool($options->getWritable());
        $this->assertIsBool($options->getReadable());
        $this->assertIsInt($options->getTtl());
        $this->assertIsString($options->getNamespace());
        $this->assertIsString($options->getKeyPattern());
    }

    public function testSetWritable(): void
    {
        $this->options->setWritable(true);
        $this->assertTrue($this->options->getWritable());

        $this->options->setWritable(false);
        $this->assertFalse($this->options->getWritable());
    }

    public function testSetReadable(): void
    {
        $this->options->setReadable(true);
        $this->assertTrue($this->options->getReadable());

        $this->options->setReadable(false);
        $this->assertFalse($this->options->getReadable());
    }

    public function testSetTtl(): void
    {
        $this->options->setTtl('123');
        $this->assertSame(123, $this->options->getTtl());
    }

    public function testSetTtlThrowsInvalidArgumentException(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->options->setTtl(-1);
    }

    public function testGetDefaultNamespaceNotEmpty(): void
    {
        $ns = $this->options->getNamespace();
        $this->assertNotEmpty($ns);
    }

    public function testSetNamespace(): void
    {
        $this->options->setNamespace('new_namespace');
        $this->assertSame('new_namespace', $this->options->getNamespace());
    }

    public function testSetNamespace0(): void
    {
        $this->options->setNamespace('0');
        $this->assertSame('0', $this->options->getNamespace());
    }

    public function testSetKeyPattern(): void
    {
        $this->options->setKeyPattern('/^[key]+$/Di');
        $this->assertEquals('/^[key]+$/Di', $this->options->getKeyPattern());
    }

    public function testUnsetKeyPattern(): void
    {
        $this->options->setKeyPattern(null);
        $this->assertSame('', $this->options->getKeyPattern());
    }

    public function testSetKeyPatternThrowsExceptionOnInvalidPattern(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->options->setKeyPattern('#');
    }

    public function testPluginRegistry(): void
    {
        $storage = $this->getMockForAbstractAdapter();

        $plugin = new MockPlugin();

        // no plugin registered
        $this->assertFalse($storage->hasPlugin($plugin));
        $this->assertEquals(0, count($storage->getPluginRegistry()));
        $this->assertEquals(0, count($plugin->getHandles()));

        // register a plugin
        $this->assertSame($storage, $storage->addPlugin($plugin));
        $this->assertTrue($storage->hasPlugin($plugin));
        $this->assertEquals(1, count($storage->getPluginRegistry()));

        // test registered callback handles
        $handles = $plugin->getHandles();
        $this->assertCount(2, $handles);

        // test unregister a plugin
        $this->assertSame($storage, $storage->removePlugin($plugin));
        $this->assertFalse($storage->hasPlugin($plugin));
        $this->assertEquals(0, count($storage->getPluginRegistry()));
        $this->assertEquals(0, count($plugin->getHandles()));
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
        $method = new ReflectionMethod(get_class($storage), 'triggerPre');
        $method->setAccessible(true);
        $rsCollection = $method->invoke($storage, 'setItem', $params);
        $this->assertInstanceOf(ResponseCollection::class, $rsCollection);

        // test called event
        $calledEvents = $plugin->getCalledEvents();
        $this->assertEquals(1, count($calledEvents));

        $event = current($calledEvents);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('setItem.pre', $event->getName());
        $this->assertSame($storage, $event->getTarget());
        $this->assertSame($params, $event->getParams());
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
        $method = new ReflectionMethod(get_class($storage), 'triggerPost');
        $method->setAccessible(true);
        $result = $method->invokeArgs($storage, ['setItem', $params, &$result]);

        // test called event
        $calledEvents = $plugin->getCalledEvents();
        $this->assertEquals(1, count($calledEvents));
        $event = current($calledEvents);

        // return value of triggerPost and the called event should be the same
        $this->assertSame($result, $event->getResult());

        $this->assertInstanceOf(PostEvent::class, $event);
        $this->assertEquals('setItem.post', $event->getName());
        $this->assertSame($storage, $event->getTarget());
        $this->assertSame($params, $event->getParams());
        $this->assertSame($result, $event->getResult());
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
        $method = new ReflectionMethod(get_class($storage), 'triggerException');
        $method->setAccessible(true);

        $this->expectException(Exception\RuntimeException::class);
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
            ->will($this->returnValue($result));

        $rs = $storage->getItem($key);
        $this->assertEquals($result, $rs);
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
            ->will($this->returnValue($result));

        $rs = $storage->getItems($keys);
        $this->assertEquals($result, $rs);
    }

    public function testInternalGetItemsCallsInternalGetItemForEachKey(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalGetItem']);

        $items  = ['key1' => 'value1', 'keyNotFound' => false, 'key2' => 'value2'];
        $result = ['key1' => 'value1', 'key2' => 'value2'];

        $consecutiveReturnCallbacks = [];
        foreach ($items as $k => $v) {
            $consecutiveReturnCallbacks[] = $this->returnCallback(function ($k, &$success) use ($items) {
                if ($items[$k]) {
                    $success = true;
                    return $items[$k];
                }

                $success = false;
                return null;
            });
        }

        $storage
            ->method('internalGetItem')
            ->with(
                $this->stringContains('key'),
                $this->anything()
            )
            ->willReturnOnConsecutiveCalls(...$consecutiveReturnCallbacks);

        $rs = $storage->getItems(array_keys($items));
        $this->assertEquals($result, $rs);
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
        $this->assertSame($result, $rs);
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
        $this->assertEquals($result, $rs);
    }

    public function testInternalHasItemsCallsInternalHasItem(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalHasItem']);

        $items = ['key1' => true];

        $storage
            ->expects($this->atLeastOnce())
            ->method('internalHasItem')
            ->with($this->equalTo('key1'))
            ->will($this->returnValue(true));

        $rs = $storage->hasItems(array_keys($items));
        $this->assertEquals(['key1'], $rs);
    }

    public function testGetItemReturnsNullIfFailed(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalGetItem']);

        $key = 'key1';

        // Do not throw exceptions outside the adapter
        $pluginOptions = new PluginOptions(
            ['throw_exceptions' => false]
        );
        $plugin        = new ExceptionHandler();
        $plugin->setOptions($pluginOptions);
        $storage->addPlugin($plugin);

        // Simulate internalGetItem() throwing an exception
        $storage
            ->expects($this->once())
            ->method('internalGetItem')
            ->with($this->equalTo($key))
            ->will($this->throwException(new \Exception('internalGetItem failed')));

        $result = $storage->getItem($key, $success);
        $this->assertNull($result, 'GetItem should return null the item cannot be retrieved');
        $this->assertFalse($success, '$success should be false if the item cannot be retrieved');
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
            ['getMetadata', 'internalGetMetadata', ['k'], []],
            ['getMetadatas', 'internalGetMetadatas', [['k1', 'k2']], ['k1' => [], 'k2' => []]],
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
            ['incrementItem', 'internalIncrementItem', ['k', 1], true],
            ['incrementItems', 'internalIncrementItems', [['k1' => 1, 'k2' => 2]], []],
            ['decrementItem', 'internalDecrementItem', ['k', 1], true],
            ['decrementItems', 'internalDecrementItems', [['k1' => 1, 'k2' => 2]], []],
            ['getCapabilities', 'internalGetCapabilities', [], $capabilities],
        ];
    }

    /**
     * @param mixed $retVal
     * @dataProvider simpleEventHandlingMethodDefinitions
     */
    public function testEventHandlingSimple(
        string $methodName,
        string $internalMethodName,
        array $methodArgs,
        $retVal
    ): void {
        $storage = $this->getMockForAbstractAdapter([$internalMethodName]);

        $eventList    = [];
        $eventHandler = function ($event) use (&$eventList) {
            $eventList[] = $event->getName();
        };
        $this->attachEventListeners($storage, $methodName, [
            'pre'       => $eventHandler,
            'post'      => $eventHandler,
            'exception' => $eventHandler,
        ]);

        $mock = $storage
            ->expects($this->once())
            ->method($internalMethodName);
        $mock = call_user_func_array([$mock, 'with'], array_map([$this, 'equalTo'], $methodArgs));
        $mock->will($this->returnValue($retVal));

        call_user_func_array([$storage, $methodName], $methodArgs);

        $expectedEventList = [
            $methodName . '.pre',
            $methodName . '.post',
        ];
        $this->assertSame($expectedEventList, $eventList);
    }

    /**
     * @dataProvider simpleEventHandlingMethodDefinitions
     */
    public function testEventHandlingCatchException(
        string $methodName,
        string $internalMethodName,
        array $methodArgs
    ): void {
        $storage = $this->getMockForAbstractAdapter([$internalMethodName]);

        $eventList    = [];
        $eventHandler = function ($event) use (&$eventList) {
            $eventList[] = $event->getName();
            if ($event instanceof ExceptionEvent) {
                $event->setThrowException(false);
            }
        };

        $this->attachEventListeners($storage, $methodName, [
            'pre'       => $eventHandler,
            'post'      => $eventHandler,
            'exception' => $eventHandler,
        ]);

        $mock = $storage
            ->expects($this->once())
            ->method($internalMethodName);
        $mock = call_user_func_array([$mock, 'with'], array_map([$this, 'equalTo'], $methodArgs));
        $mock->will($this->throwException(new \Exception('test')));

        call_user_func_array([$storage, $methodName], $methodArgs);

        $expectedEventList = [
            $methodName . '.pre',
            $methodName . '.exception',
        ];
        $this->assertSame($expectedEventList, $eventList);
    }

    /**
     * @param mixed $retVal
     * @dataProvider simpleEventHandlingMethodDefinitions
     */
    public function testEventHandlingStopInPre(
        string $methodName,
        string $internalMethodName,
        array $methodArgs,
        $retVal
    ): void {
        $storage = $this->getMockForAbstractAdapter([$internalMethodName]);

        $eventList    = [];
        $eventHandler = function ($event) use (&$eventList) {
            $eventList[] = $event->getName();
        };
        $this->attachEventListeners($storage, $methodName, [
            'pre'       => [
                $eventHandler,
                function ($event) use ($retVal) {
                    $event->stopPropagation();
                    return $retVal;
                },
            ],
            'post'      => $eventHandler,
            'exception' => $eventHandler,
        ]);

        // the internal method should never be called
        $storage->expects($this->never())->method($internalMethodName);

        // the return vaue should be available by pre-event
        $result = call_user_func_array([$storage, $methodName], $methodArgs);
        $this->assertSame($retVal, $result);

        // after the triggered pre-event the post-event should be triggered as well
        $expectedEventList = [
            $methodName . '.pre',
            $methodName . '.post',
        ];
        $this->assertSame($expectedEventList, $eventList);
    }

    public function testGetMetadatas(): void
    {
        $storage = $this->getMockForAbstractAdapter(['getMetadata', 'internalGetMetadata']);

        $meta  = ['meta' => 'data'];
        $items = [
            'key1' => $meta,
            'key2' => $meta,
        ];

        // foreach item call 'internalGetMetadata' instead of 'getMetadata'
        $storage->expects($this->never())->method('getMetadata');
        $storage->expects($this->exactly(count($items)))
            ->method('internalGetMetadata')
            ->with($this->stringContains('key'))
            ->will($this->returnValue($meta));

        $this->assertSame($items, $storage->getMetadatas(array_keys($items)));
    }

    public function testGetMetadatasFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalGetMetadata']);

        $items = ['key1', 'key2'];

        // return false to indicate that the operation failed
        $storage->expects($this->exactly(count($items)))
            ->method('internalGetMetadata')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(false));

        $this->assertSame([], $storage->getMetadatas($items));
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
        $storage->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->will($this->returnValue(true));

        $this->assertSame([], $storage->setItems($items));
    }

    public function testSetItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalSetItem']);

        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // return false to indicate that the operation failed
        $storage->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->will($this->returnValue(false));

        $this->assertSame(array_keys($items), $storage->setItems($items));
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
        $storage->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(false));

        // If not create the items using set
        // call 'internalSetItem' instead of 'setItem'
        $storage->expects($this->never())->method('setItem');
        $storage->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->will($this->returnValue(true));

        $this->assertSame([], $storage->addItems($items));
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
        $storage->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(true));

        // set item should never be called
        $storage->expects($this->never())->method('internalSetItem');

        $this->assertSame(array_keys($items), $storage->addItems($items));
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
        $storage->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(false));

        // if not create the items
        // -> return false to indicate creation failed
        $storage->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->will($this->returnValue(false));

        $this->assertSame(array_keys($items), $storage->addItems($items));
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
        $storage->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(true));

        // if yes overwrite the items
        // call 'internalSetItem' instead of 'setItem'
        $storage->expects($this->never())->method('setItem');
        $storage->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->will($this->returnValue(true));

        $this->assertSame([], $storage->replaceItems($items));
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
        $storage->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(false));

        // writing items should never be called
        $storage->expects($this->never())->method('internalSetItem');

        $this->assertSame(array_keys($items), $storage->replaceItems($items));
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
        $storage->expects($this->exactly(count($items)))
            ->method('internalHasItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(true));

        // if yes overwrite the items
        // -> return false to indicate that overwriting failed
        $storage->expects($this->exactly(count($items)))
            ->method('internalSetItem')
            ->with($this->stringContains('key'), $this->stringContains('value'))
            ->will($this->returnValue(false));

        $this->assertSame(array_keys($items), $storage->replaceItems($items));
    }

    public function testRemoveItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['removeItem', 'internalRemoveItem']);

        $keys = ['key1', 'key2'];

        // call 'internalRemoveItem' instaed of 'removeItem'
        $storage->expects($this->never())->method('removeItem');
        $storage->expects($this->exactly(count($keys)))
            ->method('internalRemoveItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(true));

        $this->assertSame([], $storage->removeItems($keys));
    }

    public function testRemoveItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalRemoveItem']);

        $keys = ['key1', 'key2', 'key3'];

        // call 'internalRemoveItem'
        // -> return false to indicate that no item was removed
        $storage->expects($this->exactly(count($keys)))
            ->method('internalRemoveItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(false));

        $this->assertSame($keys, $storage->removeItems($keys));
    }

    public function testIncrementItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['incrementItem', 'internalIncrementItem']);

        $items = [
            'key1' => 2,
            'key2' => 2,
        ];

        // foreach item call 'internalIncrementItem' instead of 'incrementItem'
        $storage->expects($this->never())->method('incrementItem');
        $storage->expects($this->exactly(count($items)))
            ->method('internalIncrementItem')
            ->with($this->stringContains('key'), $this->equalTo(2))
            ->will($this->returnValue(4));

        $this->assertSame([
            'key1' => 4,
            'key2' => 4,
        ], $storage->incrementItems($items));
    }

    public function testIncrementItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalIncrementItem']);

        $items = [
            'key1' => 2,
            'key2' => 2,
        ];

        // return false to indicate that the operation failed
        $storage->expects($this->exactly(count($items)))
            ->method('internalIncrementItem')
            ->with($this->stringContains('key'), $this->equalTo(2))
            ->will($this->returnValue(false));

        $this->assertSame([], $storage->incrementItems($items));
    }

    public function testDecrementItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['decrementItem', 'internalDecrementItem']);

        $items = [
            'key1' => 2,
            'key2' => 2,
        ];

        // foreach item call 'internalDecrementItem' instead of 'decrementItem'
        $storage->expects($this->never())->method('decrementItem');
        $storage->expects($this->exactly(count($items)))
            ->method('internalDecrementItem')
            ->with($this->stringContains('key'), $this->equalTo(2))
            ->will($this->returnValue(4));

        $this->assertSame([
            'key1' => 4,
            'key2' => 4,
        ], $storage->decrementItems($items));
    }

    public function testDecrementItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalDecrementItem']);

        $items = [
            'key1' => 2,
            'key2' => 2,
        ];

        // return false to indicate that the operation failed
        $storage->expects($this->exactly(count($items)))
            ->method('internalDecrementItem')
            ->with($this->stringContains('key'), $this->equalTo(2))
            ->will($this->returnValue(false));

        $this->assertSame([], $storage->decrementItems($items));
    }

    public function testTouchItems(): void
    {
        $storage = $this->getMockForAbstractAdapter(['touchItem', 'internalTouchItem']);

        $items = ['key1', 'key2'];

        // foreach item call 'internalTouchItem' instead of 'touchItem'
        $storage->expects($this->never())->method('touchItem');
        $storage->expects($this->exactly(count($items)))
            ->method('internalTouchItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(true));

        $this->assertSame([], $storage->touchItems($items));
    }

    public function testTouchItemsFail(): void
    {
        $storage = $this->getMockForAbstractAdapter(['internalTouchItem']);

        $items = ['key1', 'key2'];

        // return false to indicate that the operation failed
        $storage->expects($this->exactly(count($items)))
            ->method('internalTouchItem')
            ->with($this->stringContains('key'))
            ->will($this->returnValue(false));

        $this->assertSame($items, $storage->touchItems($items));
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

        // getMetadata(s)
        $this->checkPreEventCanChangeArguments('getMetadata', [
            'key' => 'key',
        ], [
            'key' => 'changedKey',
        ]);

        $this->checkPreEventCanChangeArguments('getMetadatas', [
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

        // incrementItem(s)
        $this->checkPreEventCanChangeArguments('incrementItem', [
            'key'   => 'key',
            'value' => 1,
        ], [
            'key'   => 'changedKey',
            'value' => 2,
        ]);

        $this->checkPreEventCanChangeArguments('incrementItems', [
            'keyValuePairs' => ['key' => 1],
        ], [
            'keyValuePairs' => ['changedKey' => 2],
        ]);

        // decrementItem(s)
        $this->checkPreEventCanChangeArguments('decrementItem', [
            'key'   => 'key',
            'value' => 1,
        ], [
            'key'   => 'changedKey',
            'value' => 2,
        ]);

        $this->checkPreEventCanChangeArguments('decrementItems', [
            'keyValuePairs' => ['key' => 1],
        ], [
            'keyValuePairs' => ['changedKey' => 2],
        ]);
    }

    protected function checkPreEventCanChangeArguments(string $method, array $args, array $expectedArgs): void
    {
        $internalMethod = 'internal' . ucfirst($method);
        $eventName      = $method . '.pre';

        // init mock
        $storage = $this->getMockForAbstractAdapter([$internalMethod]);
        $storage->getEventManager()->attach($eventName, function ($event) use ($expectedArgs) {
            $params = $event->getParams();
            foreach ($expectedArgs as $k => $v) {
                $params[$k] = $v;
            }
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
     * @param array<int,string> $methods
     * @return AbstractAdapter&MockObject
     */
    protected function getMockForAbstractAdapter(array $methods = []): AbstractAdapter
    {
        $adapter = $this->createMockForAbstractAdapter($methods);

        assert($adapter instanceof AbstractAdapter);

        $this->options = $this->options ?? new AdapterOptions();
        $adapter->setOptions($this->options);
        return $adapter;
    }

    /**
     * @param array<int,string> $methods
     */
    private function createMockForAbstractAdapter(array $methods): AbstractAdapter
    {
        if (! $methods) {
            return $this->getMockForAbstractClass(AbstractAdapter::class);
        }

        $reflection = new ReflectionClass(AbstractAdapter::class);
        foreach ($reflection->getMethods() as $method) {
            if ($method->isAbstract()) {
                $methods[] = $method->getName();
            }
        }

        return $this->getMockBuilder(AbstractAdapter::class)
            ->onlyMethods(array_unique($methods))
            ->disableArgumentCloning()
            ->getMock();
    }

    protected function attachEventListeners(
        AbstractAdapter $adapter,
        string $methodName,
        array $listenersByEventName
    ): void {
        $eventManager = $adapter->getEventManager();
        foreach ($listenersByEventName as $eventName => $listenersOrListener) {
            if (! is_array($listenersOrListener)) {
                $listenersOrListener = [$listenersOrListener];
            }

            foreach ($listenersOrListener as $listener) {
                assert(is_callable($listener));
                $eventManager->attach(sprintf('%s.%s', $methodName, $eventName), $listener);
            }
        }
    }
}
