<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Plugin;

use ArrayObject;
use Laminas\Cache;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use LaminasTest\Cache\Storage\TestAsset\OptimizableMockAdapter;

/**
 * @covers Laminas\Cache\Storage\Plugin\OptimizeByFactor<extended>
 */
class OptimizeByFactorTest extends CommonPluginTest
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

    public function setUp(): void
    {
        $this->_adapter = new OptimizableMockAdapter();
        $this->_options = new Cache\Storage\Plugin\PluginOptions([
            'optimizing_factor' => 1,
        ]);
        $this->_plugin  = new Cache\Storage\Plugin\OptimizeByFactor();
        $this->_plugin->setOptions($this->_options);
    }

    public function getCommonPluginNamesProvider()
    {
        return [
            ['optimize_by_factor'],
            ['optimizebyfactor'],
            ['OptimizeByFactor'],
            ['optimizeByFactor'],
        ];
    }

    public function testAddPlugin(): void
    {
        $this->_adapter->addPlugin($this->_plugin);

        // check attached callbacks
        $expectedListeners = [
            'removeItem.post'  => 'optimizeByFactor',
            'removeItems.post' => 'optimizeByFactor',
        ];
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->getArrayOfListenersForEvent($eventName, $this->_adapter->getEventManager());

            // event should attached only once
            $this->assertSame(1, count($listeners));

            // check expected callback method
            $cb = array_shift($listeners);
            $this->assertArrayHasKey(0, $cb);
            $this->assertSame($this->_plugin, $cb[0]);
            $this->assertArrayHasKey(1, $cb);
            $this->assertSame($expectedCallbackMethod, $cb[1]);
        }
    }

    public function testRemovePlugin(): void
    {
        $this->_adapter->addPlugin($this->_plugin);
        $this->_adapter->removePlugin($this->_plugin);

        // no events should be attached
        $this->assertEquals(0, count($this->getEventsFromEventManager($this->_adapter->getEventManager())));
    }

    public function testOptimizeByFactor(): void
    {
        $adapter = $this->getMockBuilder(get_class($this->_adapter))
            ->setMethods(['optimize'])
            ->getMock();

        // test optimize will be called
        $adapter
            ->expects($this->once())
            ->method('optimize');

        // call event callback
        $result = true;
        $event = new PostEvent('removeItem.post', $adapter, new ArrayObject([
            'options' => []
        ]), $result);

        $this->_plugin->optimizeByFactor($event);

        $this->assertTrue($event->getResult());
    }
}
