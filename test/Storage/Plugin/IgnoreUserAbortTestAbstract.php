<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Plugin;

use Laminas\Cache;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;

use function array_shift;
use function count;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Storage\Plugin\IgnoreUserAbort<extended>
 */
class IgnoreUserAbortTestAbstract extends AbstractCommonPluginTest
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
        $this->plugin  = new Cache\Storage\Plugin\IgnoreUserAbort();
        $this->plugin->setOptions($this->options);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPluginNamesProvider()
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
            'incrementItem.pre'         => 'onBefore',
            'incrementItem.post'        => 'onAfter',
            'incrementItem.exception'   => 'onAfter',
            'incrementItems.pre'        => 'onBefore',
            'incrementItems.post'       => 'onAfter',
            'incrementItems.exception'  => 'onAfter',
            'decrementItem.pre'         => 'onBefore',
            'decrementItem.post'        => 'onAfter',
            'decrementItem.exception'   => 'onAfter',
            'decrementItems.pre'        => 'onBefore',
            'decrementItems.post'       => 'onAfter',
            'decrementItems.exception'  => 'onAfter',
        ];
        foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
            $listeners = $this->getArrayOfListenersForEvent($eventName, $this->adapter->getEventManager());

            // event should attached only once
            self::assertSame(1, count($listeners));

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
        self::assertEquals(0, count($this->getEventsFromEventManager($this->adapter->getEventManager())));
    }
}
