<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Exception;
use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Event;
use LaminasTest\Cache\Storage\Adapter\TestAsset\AdapterOptionsWithPrioritizedOptions;
use LaminasTest\Cache\Storage\TestAsset\MockAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function func_get_args;

#[Group('Laminas_Cache')]
#[CoversClass(AdapterOptions::class)]
final class AdapterOptionsTest extends TestCase
{
    protected AdapterOptions $options;

    public function setUp(): void
    {
        $this->options = new AdapterOptions();
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
        $this->options->setKeyPattern('');
        self::assertSame('', $this->options->getKeyPattern());
    }

    public function testSetKeyPatternThrowsExceptionOnInvalidPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->options->setKeyPattern('#');
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
        $adapter = new MockAdapter();
        $this->options->setAdapter($adapter);

        // setup event listener
        $calledArgs = null;
        $adapter->getEventManager()->attach('option', static function () use (&$calledArgs): void {
            $calledArgs = func_get_args();
        });

        // trigger by changing an option
        $this->options->setWritable(false);

        self::assertIsArray($calledArgs);
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
