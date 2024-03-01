<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Plugin;

use Laminas\Cache;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Plugin\PluginOptions;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;

use function array_shift;

final class IgnoreUserAbortTest extends AbstractCommonPluginTest
{
    use EventListenerIntrospectionTrait;

    private AbstractAdapter $adapter;

    private PluginOptions $options;

    protected function setUp(): void
    {
        $this->adapter = $this->getMockForAbstractClass(AbstractAdapter::class);
        $this->options = new Cache\Storage\Plugin\PluginOptions();
        $this->plugin  = new Cache\Storage\Plugin\IgnoreUserAbort();
        $this->plugin->setOptions($this->options);
    }

    public function getCommonPluginNamesProvider(): array
    {
        return [
            'lowercase with underscore' => ['ignore_user_abort'],
            'lowercase'                 => ['ignoreuserabort'],
            'UpperCamelCase'            => ['IgnoreUserAbort'],
            'camelCase'                 => ['ignoreUserAbort'],
        ];
    }

    public function testAddPlugin(): void
    {
        $this->adapter->addPlugin($this->plugin);

        // check attached callbacks
        $expectedListeners = [
            'setItem.pre'               => 'onBefore',
            'setItem.post'              => 'onAfter',
            'setItem.exception'         => 'onAfter',
            'setItems.pre'              => 'onBefore',
            'setItems.post'             => 'onAfter',
            'setItems.exception'        => 'onAfter',
            'addItem.pre'               => 'onBefore',
            'addItem.post'              => 'onAfter',
            'addItem.exception'         => 'onAfter',
            'addItems.pre'              => 'onBefore',
            'addItems.post'             => 'onAfter',
            'addItems.exception'        => 'onAfter',
            'replaceItem.pre'           => 'onBefore',
            'replaceItem.post'          => 'onAfter',
            'replaceItem.exception'     => 'onAfter',
            'replaceItems.pre'          => 'onBefore',
            'replaceItems.post'         => 'onAfter',
            'replaceItems.exception'    => 'onAfter',
            'checkAndSetItem.pre'       => 'onBefore',
            'checkAndSetItem.post'      => 'onAfter',
            'checkAndSetItem.exception' => 'onAfter',
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
}
