<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Plugin;

use ArrayObject;
use Laminas\Cache;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use LaminasTest\Cache\Storage\TestAsset\OptimizableMockAdapter;

use function array_shift;
use function get_class;

final class OptimizeByFactorTest extends AbstractCommonPluginTest
{
    use EventListenerIntrospectionTrait;

    protected OptimizableMockAdapter $adapter;

    private PluginOptions $options;

    protected function setUp(): void
    {
        $this->adapter = new OptimizableMockAdapter();
        $this->options = new Cache\Storage\Plugin\PluginOptions([
            'optimizing_factor' => 1,
        ]);
        $this->plugin  = new Cache\Storage\Plugin\OptimizeByFactor();
        $this->plugin->setOptions($this->options);
    }

    public function getCommonPluginNamesProvider(): array
    {
        return [
            'lowercase with underscore' => ['optimize_by_factor'],
            'lowercase'                 => ['optimizebyfactor'],
            'UpperCamelCase'            => ['OptimizeByFactor'],
            'camelCase'                 => ['optimizeByFactor'],
        ];
    }

    public function testAddPlugin(): void
    {
        $this->adapter->addPlugin($this->plugin);

        // check attached callbacks
        $expectedListeners = [
            'removeItem.post'  => 'optimizeByFactor',
            'removeItems.post' => 'optimizeByFactor',
        ];
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->getArrayOfListenersForEvent($eventName, $this->adapter->getEventManager());

            // event should attached only once
            self::assertCount(1, $listeners);

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
        self::assertCount(0, $this->getEventsFromEventManager($this->adapter->getEventManager()));
    }

    public function testOptimizeByFactor(): void
    {
        $adapter = $this->getMockBuilder(get_class($this->adapter))
            ->setMethods(['optimize'])
            ->getMock();

        // test optimize will be called
        $adapter
            ->expects($this->once())
            ->method('optimize');

        // call event callback
        $result = true;
        $event  = new PostEvent('removeItem.post', $adapter, new ArrayObject([
            'options' => [],
        ]), $result);

        $this->plugin->optimizeByFactor($event);

        self::assertTrue($event->getResult());
    }
}
