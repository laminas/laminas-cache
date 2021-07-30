<?php

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Exception;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Storage\Adapter\TestAsset\AdapterOptionsWithPrioritizedOptions;
use PHPUnit\Framework\TestCase;

use function func_get_args;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\AdapterOptions<extended>
 */
class AdapterOptionsTest extends TestCase
{
    /**
     * Mock of the storage
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Adapter options
     *
     * @var null|AdapterOptions
     */
    protected $options;

    public function setUp(): void
    {
        $this->options = new AdapterOptions();
    }

    public function testKeyPattern(): void
    {
        // test default value
        self::assertSame('', $this->options->getKeyPattern());

        self::assertSame($this->options, $this->options->setKeyPattern('/./'));
        self::assertSame('/./', $this->options->getKeyPattern());
    }

    public function testSetKeyPatternAllowEmptyString(): void
    {
        // first change to something different as an empty string is the default
        $this->options->setKeyPattern('/.*/');

        $this->options->setKeyPattern('');
        self::assertSame('', $this->options->getKeyPattern());
    }

    public function testSetKeyPatternThrowsInvalidArgumentExceptionOnInvalidPattern(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->options->setKeyPattern('foo bar');
    }

    public function testNamespace(): void
    {
        self::assertSame($this->options, $this->options->setNamespace('foobar'));
        self::assertSame('foobar', $this->options->getNamespace());
    }

    public function testReadable(): void
    {
        self::assertSame($this->options, $this->options->setReadable(false));
        self::assertSame(false, $this->options->getReadable());

        self::assertSame($this->options, $this->options->setReadable(true));
        self::assertSame(true, $this->options->getReadable());
    }

    public function testWritable(): void
    {
        self::assertSame($this->options, $this->options->setWritable(false));
        self::assertSame(false, $this->options->getWritable());

        self::assertSame($this->options, $this->options->setWritable(true));
        self::assertSame(true, $this->options->getWritable());
    }

    public function testTtl(): void
    {
        // infinite default value
        self::assertSame(0, $this->options->getTtl());

        self::assertSame($this->options, $this->options->setTtl(12345));
        self::assertSame(12345, $this->options->getTtl());
    }

    public function testSetTtlThrowsInvalidArgumentExceptionOnNegativeValue(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->options->setTtl(-1);
    }

    public function testSetTtlAutoconvertToIntIfPossible(): void
    {
        $this->options->setTtl(12345.0);
        self::assertSame(12345, $this->options->getTtl());

        $this->options->setTtl(12345.678);
        self::assertSame(12345.678, $this->options->getTtl());
    }

    public function testTriggerOptionEvent(): void
    {
        // setup an adapter implements EventsCapableInterface
        $adapter = $this->getMockForAbstractClass(AbstractAdapter::class);
        $this->options->setAdapter($adapter);

        // setup event listener
        $calledArgs = null;
        $adapter->getEventManager()->attach('option', function () use (&$calledArgs) {
            $calledArgs = func_get_args();
        });

        // trigger by changing an option
        $this->options->setWritable(false);

        // assert (hopefully) called listener and arguments
        self::assertCount(1, $calledArgs, '"option" event was not triggered or got a wrong number of arguments');
        /** @var Event|null $event */
        $event = $calledArgs[0] ?? null;
        self::assertInstanceOf(Event::class, $event);
        self::assertEquals(['writable' => false], $event->getParams()->getArrayCopy());
    }

    public function testSetFromArrayWithoutPrioritizedOptions(): void
    {
        self::assertSame($this->options, $this->options->setFromArray([
            'kEy_pattERN' => '/./',
            'nameSPACE'   => 'foobar',
        ]));
        self::assertSame('/./', $this->options->getKeyPattern());
        self::assertSame('foobar', $this->options->getNamespace());
    }

    public function testSetFromArrayWithPrioritizedOptions(): void
    {
        $options = new AdapterOptionsWithPrioritizedOptions();

        // send unordered options array
        self::assertSame($options, $options->setFromArray([
            'nAmeSpace'   => 'foobar',
            'WriTAble'    => false,
            'KEY_paTTern' => '/./',
        ]));

        self::assertEquals('foobar', $options->getNamespace());
        self::assertFalse($options->getWritable());
        self::assertEquals('/./', $options->getKeyPattern());
    }
}
