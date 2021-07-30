<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Pattern\AbstractStorageCapablePattern;
use Laminas\Cache\Pattern\CallbackCache;
use Laminas\Cache\Pattern\ClassCache;
use Laminas\Cache\Pattern\ObjectCache;
use Laminas\Cache\Pattern\OutputCache;
use Laminas\Cache\Pattern\StoragePatternCacheFactory;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class StoragePatternCacheFactoryTest extends TestCase
{
    /** @var StoragePatternCacheFactory */
    private $factory;

    /** @var MockObject&ContainerInterface */
    private $container;

    public function builtInCachePattern(): array
    {
        return [
            [CallbackCache::class],
            [ClassCache::class],
            [ObjectCache::class],
            [OutputCache::class],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory   = new StoragePatternCacheFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testWillThrowInvalidArgumentExceptionWhenRequestedNameIsNotAFullyQualifiedClassName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please only use this factory for services with full qualified class names');
        ($this->factory)($this->container, 'foo');
    }

    /**
     * @dataProvider builtInCachePattern
     */
    public function testWillInstantiateCachePattern(string $className): void
    {
        $instance = ($this->factory)($this->container, $className);
        self::assertInstanceOf($className, $instance);
    }

    /**
     * @dataProvider builtInCachePattern
     */
    public function testWillInstantiateCachePatternWithOptions(string $className): void
    {
        $instance = ($this->factory)($this->container, $className, [
            'fileLocking' => false,
        ]);
        self::assertInstanceOf($className, $instance);
        $options = $instance->getOptions();
        self::assertFalse($options->getFileLocking());
    }

    /**
     * @dataProvider builtInCachePattern
     */
    public function testWillInstantiateCachePatternWithOptionsAndStorage(string $className): void
    {
        $storage  = $this->createMock(StorageInterface::class);
        $instance = ($this->factory)($this->container, $className, [
            'fileLocking' => false,
            'storage'     => $storage,
        ]);
        self::assertInstanceOf($className, $instance);
        self::assertInstanceOf(AbstractStorageCapablePattern::class, $instance);
        $options = $instance->getOptions();
        self::assertFalse($options->getFileLocking());
        self::assertSame($storage, $instance->getStorage());
    }

    /**
     * @dataProvider builtInCachePattern
     */
    public function testWillInstantiateCachePatternWithStorageOptionAsArray(string $className): void
    {
        $callbackCalled = false;
        $storageOptions = [
            'name' => 'foo',
        ];

        $storage = $this->createMock(StorageInterface::class);

        $callback = function (
            ContainerInterface $container,
            array $option
        ) use (
            &$callbackCalled,
            $storageOptions,
            $storage
        ): StorageInterface {
            self::assertEquals($storageOptions, $option);
            self::assertSame($this->container, $container);
            $callbackCalled = true;
            return $storage;
        };

        $factory = new StoragePatternCacheFactory($callback);

        $instance = $factory($this->container, $className, [
            'storage' => $storageOptions,
        ]);

        self::assertInstanceOf($className, $instance);
        self::assertInstanceOf(AbstractStorageCapablePattern::class, $instance);
        self::assertSame($storage, $instance->getStorage());
        self::assertTrue($callbackCalled);
    }
}
