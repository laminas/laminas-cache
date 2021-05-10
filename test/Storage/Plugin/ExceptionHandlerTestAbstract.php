<?php

namespace LaminasTest\Cache\Storage\Plugin;

use ArrayObject;
use Exception;
use Laminas\Cache;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\ExceptionEvent;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;

use function array_shift;
use function count;

/**
 * @covers \Laminas\Cache\Storage\Plugin\ExceptionHandler<extended>
 */
class ExceptionHandlerTestAbstract extends AbstractCommonPluginTest
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
        $this->adapter = new MockAdapter();
        $this->options = new Cache\Storage\Plugin\PluginOptions();
        $this->plugin  = new Cache\Storage\Plugin\ExceptionHandler();
        $this->plugin->setOptions($this->options);

        parent::setUp();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPluginNamesProvider()
    {
        return [
            'lowercase with underscore' => ['exception_handler'],
            'lowercase'                 => ['exceptionhandler'],
            'UpperCamelCase'            => ['ExceptionHandler'],
            'camelCase'                 => ['exceptionHandler'],
        ];
    }

    public function testAddPlugin(): void
    {
        $this->adapter->addPlugin($this->plugin);

        // check attached callbacks
        $expectedListeners = [
            'getItem.exception'         => 'onException',
            'getItems.exception'        => 'onException',
            'hasItem.exception'         => 'onException',
            'hasItems.exception'        => 'onException',
            'getMetadata.exception'     => 'onException',
            'getMetadatas.exception'    => 'onException',
            'setItem.exception'         => 'onException',
            'setItems.exception'        => 'onException',
            'addItem.exception'         => 'onException',
            'addItems.exception'        => 'onException',
            'replaceItem.exception'     => 'onException',
            'replaceItems.exception'    => 'onException',
            'touchItem.exception'       => 'onException',
            'touchItems.exception'      => 'onException',
            'removeItem.exception'      => 'onException',
            'removeItems.exception'     => 'onException',
            'checkAndSetItem.exception' => 'onException',
            'incrementItem.exception'   => 'onException',
            'incrementItems.exception'  => 'onException',
            'decrementItem.exception'   => 'onException',
            'decrementItems.exception'  => 'onException',
            'clearExpired.exception'    => 'onException',
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

    public function testOnExceptionCallCallback(): void
    {
        $expectedException = new Exception();
        $callbackCalled    = false;

        $this->options->setExceptionCallback(function ($exception) use ($expectedException, &$callbackCalled) {
            $callbackCalled = $exception === $expectedException;
        });

        // run onException
        $result = null;
        $event  = new ExceptionEvent('getItem.exception', $this->adapter, new ArrayObject([
            'key'     => 'key',
            'options' => [],
        ]), $result, $expectedException);
        $this->plugin->onException($event);

        self::assertTrue(
            $callbackCalled,
            "Expected callback wasn't called or the expected exception wasn't the first argument"
        );
    }

    public function testDontThrowException(): void
    {
        $this->options->setThrowExceptions(false);

        // run onException
        $result = 'test';
        $event  = new ExceptionEvent('getItem.exception', $this->adapter, new ArrayObject([
            'key'     => 'key',
            'options' => [],
        ]), $result, new Exception());
        $this->plugin->onException($event);

        self::assertFalse($event->getThrowException());
        self::assertSame('test', $event->getResult());
    }
}
