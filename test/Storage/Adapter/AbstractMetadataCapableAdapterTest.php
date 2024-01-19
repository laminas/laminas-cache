<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Exception;
use Laminas\Cache;
use Laminas\Cache\Storage\AbstractMetadataCapableAdapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Event;
use Laminas\EventManager\EventInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function call_user_func_array;
use function count;
use function ucfirst;

final class AbstractMetadataCapableAdapterTest extends TestCase
{
    private ?AdapterOptions $options;

    public function setUp(): void
    {
        $this->options = new AdapterOptions();
    }

    /**
     * @return list<array{
     *     0: non-empty-string,
     *     1: non-empty-string,
     *     2: array,
     *     3: array|null
     * }>
     */
    public function simpleEventHandlingMethodDefinitions(): array
    {
        return [
            //    name, internalName, args, returnValue
            ['getMetadata', 'internalGetMetadata', ['k'], null],
            ['getMetadatas', 'internalGetMetadatas', [['k1', 'k2']], []],
        ];
    }

    /**
     * Generates a mock of the abstract metadata capable storage adapter by mocking all abstract and the given methods
     * Also sets the adapter options
     *
     * @psalm-param list<non-empty-string> $methods
     */
    private function getMockForAbstractMetadataCapableAdapter(
        array $methods = []
    ): MockObject&AbstractMetadataCapableAdapter {
        if (! $methods) {
            $adapter = $this->getMockForAbstractClass(AbstractMetadataCapableAdapter::class);
        } else {
            $reflection = new ReflectionClass(AbstractMetadataCapableAdapter::class);
            foreach ($reflection->getMethods() as $method) {
                if ($method->isAbstract()) {
                    $methods[] = $method->getName();
                }
            }
            $adapter = $this->getMockBuilder(AbstractMetadataCapableAdapter::class)
                ->onlyMethods(array_unique($methods))
                ->disableArgumentCloning()
                ->getMock();
        }

        $adapter->setOptions($this->options ?? new AdapterOptions());

        return $adapter;
    }

    /**
     * @psalm-param non-empty-string $methodName
     * @psalm-param non-empty-string $internalMethodName
     * @dataProvider simpleEventHandlingMethodDefinitions
     */
    public function testEventHandlingSimple(
        string $methodName,
        string $internalMethodName,
        array $methodArgs,
        mixed $retVal
    ): void {
        $storage = $this->getMockForAbstractMetadataCapableAdapter([$internalMethodName]);

        $eventList    = [];
        $eventHandler = static function (Event $event) use (&$eventList): void {
            $eventList[] = $event->getName();
        };
        $eventManager = $storage->getEventManager();
        $eventManager->attach($methodName . '.pre', $eventHandler);
        $eventManager->attach($methodName . '.post', $eventHandler);
        $eventManager->attach($methodName . '.exception', $eventHandler);

        $storage
            ->expects($this->once())
            ->method($internalMethodName)
            ->with(...array_map([$this, 'equalTo'], $methodArgs))
            ->willReturn($retVal);

        call_user_func_array([$storage, $methodName], $methodArgs);

        $expectedEventList = [
            $methodName . '.pre',
            $methodName . '.post',
        ];

        self::assertSame($expectedEventList, $eventList);
    }

    /**
     * @psalm-param non-empty-string $methodName
     * @psalm-param non-empty-string $internalMethodName
     * @dataProvider simpleEventHandlingMethodDefinitions
     */
    public function testEventHandlingCatchException(
        string $methodName,
        string $internalMethodName,
        array $methodArgs
    ): void {
        $storage = $this->getMockForAbstractMetadataCapableAdapter([$internalMethodName]);

        $eventList    = [];
        $eventHandler = static function (Event $event) use (&$eventList): void {
            $eventList[] = $event->getName();
            if ($event instanceof Cache\Storage\ExceptionEvent) {
                $event->setThrowException(false);
            }
        };
        $eventManager = $storage->getEventManager();
        $eventManager->attach($methodName . '.pre', $eventHandler);
        $eventManager->attach($methodName . '.post', $eventHandler);
        $eventManager->attach($methodName . '.exception', $eventHandler);

        $storage
            ->expects($this->once())
            ->method($internalMethodName)
            ->with(...array_map([self::class, 'equalTo'], $methodArgs))
            ->willThrowException(new Exception('test'));

        call_user_func_array([$storage, $methodName], $methodArgs);

        $expectedEventList = [
            $methodName . '.pre',
            $methodName . '.exception',
        ];
        self::assertSame($expectedEventList, $eventList);
    }

    /**
     * @psalm-param non-empty-string $methodName
     * @psalm-param non-empty-string $internalMethodName
     * @dataProvider simpleEventHandlingMethodDefinitions
     */
    public function testEventHandlingStopInPre(
        string $methodName,
        string $internalMethodName,
        array $methodArgs,
        mixed $retVal
    ): void {
        $storage = $this->getMockForAbstractMetadataCapableAdapter([$internalMethodName]);

        $eventList    = [];
        $eventHandler = static function (Event $event) use (&$eventList): void {
            $eventList[] = $event->getName();
        };
        $eventManager = $storage->getEventManager();
        $eventManager->attach($methodName . '.pre', $eventHandler);
        $eventManager->attach($methodName . '.post', $eventHandler);
        $eventManager->attach($methodName . '.exception', $eventHandler);

        $eventManager->attach($methodName . '.pre', static function (EventInterface $event) use ($retVal): mixed {
            $event->stopPropagation();
            return $retVal;
        });

        // the internal method should never be called
        $storage->expects($this->never())->method($internalMethodName);

        // the return value should be available by pre-event
        $result = call_user_func_array([$storage, $methodName], $methodArgs);
        self::assertSame($retVal, $result);

        // after the triggered pre-event the post-event should be triggered as well
        $expectedEventList = [
            $methodName . '.pre',
            $methodName . '.post',
        ];
        self::assertSame($expectedEventList, $eventList);
    }

    public function testGetMetadatas(): void
    {
        $storage = $this->getMockForAbstractMetadataCapableAdapter(['getMetadata', 'internalGetMetadata']);

        $meta = new class {
            public string $meta = 'data';
        };

        $items = [
            'key1' => $meta,
            'key2' => $meta,
        ];

        // foreach item call 'internalGetMetadata' instead of 'getMetadata'
        $storage->expects($this->never())->method('getMetadata');
        $storage->expects($this->exactly(count($items)))
            ->method('internalGetMetadata')
            ->with($this->stringContains('key'))
            ->willReturn($meta);

        self::assertSame($items, $storage->getMetadatas(array_keys($items)));
    }

    public function testGetMetadatasFail(): void
    {
        $storage = $this->getMockForAbstractMetadataCapableAdapter(['internalGetMetadata']);

        $items = ['key1', 'key2'];

        // return false to indicate that the operation failed
        $storage
            ->expects($this->exactly(count($items)))
            ->method('internalGetMetadata')
            ->with($this->stringContains('key'))
            ->willReturn(null);

        self::assertSame([], $storage->getMetadatas($items));
    }

    public function testFoo(): void
    {
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
    }

    /**
     * @param non-empty-string $method
     * @param array<string,list<string>>|array<string,string>  $args
     * @param array<string,list<string>>|array<string,string>  $expectedArgs
     */
    protected function checkPreEventCanChangeArguments(string $method, array $args, array $expectedArgs): void
    {
        $internalMethod = 'internal' . ucfirst($method);
        $eventName      = $method . '.pre';

        // init mock
        $storage = $this->getMockForAbstractMetadataCapableAdapter([$internalMethod]);
        $storage->getEventManager()->attach($eventName, static function (Event $event) use ($expectedArgs): void {
            $params = $event->getParams();
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
}
