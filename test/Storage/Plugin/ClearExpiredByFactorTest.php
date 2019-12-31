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
use LaminasTest\Cache\Storage\TestAsset\ClearExpiredMockAdapter;

class ClearExpiredByFactorTest extends CommonPluginTest
{
    /**
     * The storage adapter
     *
     * @var LaminasTest\Cache\Storage\TestAsset\MockAdapter
     */
    protected $_adapter;

    public function setUp()
    {
        $this->_adapter = new ClearExpiredMockAdapter();
        $this->_options = new Cache\Storage\Plugin\PluginOptions([
            'clearing_factor' => 1,
        ]);
        $this->_plugin  = new Cache\Storage\Plugin\ClearExpiredByFactor();
        $this->_plugin->setOptions($this->_options);

        parent::setUp();
    }

    public function testAddPlugin()
    {
        $this->_adapter->addPlugin($this->_plugin);

        // check attached callbacks
        $expectedListeners = [
            'setItem.post'  => 'clearExpiredByFactor',
            'setItems.post' => 'clearExpiredByFactor',
            'addItem.post'  => 'clearExpiredByFactor',
            'addItems.post' => 'clearExpiredByFactor',
        ];
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->_adapter->getEventManager()->getListeners($eventName);

            // event should attached only once
            $this->assertSame(1, $listeners->count());

            // check expected callback method
            $cb = $listeners->top()->getCallback();
            $this->assertArrayHasKey(0, $cb);
            $this->assertSame($this->_plugin, $cb[0]);
            $this->assertArrayHasKey(1, $cb);
            $this->assertSame($expectedCallbackMethod, $cb[1]);
        }
    }

    public function testRemovePlugin()
    {
        $this->_adapter->addPlugin($this->_plugin);
        $this->_adapter->removePlugin($this->_plugin);

        // no events should be attached
        $this->assertEquals(0, count($this->_adapter->getEventManager()->getEvents()));
    }

    public function testClearExpiredByFactor()
    {
        $adapter = $this->getMock(get_class($this->_adapter), ['clearExpired']);
        $this->_options->setClearingFactor(1);

        // test clearByNamespace will be called
        $adapter
            ->expects($this->once())
            ->method('clearExpired')
            ->will($this->returnValue(true));

        // call event callback
        $result = true;
        $event = new PostEvent('setItem.post', $adapter, new ArrayObject([
            'options' => [],
        ]), $result);
        $this->_plugin->clearExpiredByFactor($event);

        $this->assertTrue($event->getResult());
    }
}
