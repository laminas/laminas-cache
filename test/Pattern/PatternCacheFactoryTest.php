<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Pattern\CaptureCache;
use Laminas\Cache\Pattern\PatternCacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class PatternCacheFactoryTest extends TestCase
{
    /**
     * @var PatternCacheFactory
     */
    private $factory;

    /**
     * @var MockObject&ContainerInterface
     */
    private $container;

    public function builtInCachePattern(): array
    {
        return [
            [CaptureCache::class],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PatternCacheFactory();
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
}
