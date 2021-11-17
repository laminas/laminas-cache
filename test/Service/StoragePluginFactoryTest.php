<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Generator;
use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Service\StoragePluginFactory;
use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StoragePluginFactoryTest extends TestCase
{
    /** @var PluginManagerInterface&MockObject */
    private $plugins;

    /** @var StoragePluginFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugins = $this->createMock(PluginManagerInterface::class);
        $this->factory = new StoragePluginFactory($this->plugins);
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:array<mixed>,1:non-empty-string}>
     */
    public function invalidConfigurations(): Generator
    {
        yield 'empty map' => [
            [],
            'Configuration must be a non-empty array',
        ];

        yield 'missing adapter' => [
            ['options' => []],
            'Configuration must contain a "adapter" key',
        ];

        yield 'empty adapter name' => [
            ['adapter' => ''],
            'Plugin "adapter" has to be a non-empty string',
        ];

        yield 'invalid options' => [
            ['adapter' => 'foo', 'options' => 'bar'],
            'Plugin "options" must be an array with string keys',
        ];
    }

    public function testWillCreatePluginFromArrayConfiguration(): void
    {
        $plugin = $this->createMock(PluginInterface::class);

        $this->plugins
            ->expects(self::once())
            ->method('build')
            ->with('foo')
            ->willReturn($plugin);

        $createdPlugin = $this->factory->createFromArrayConfiguration(['adapter' => 'foo']);
        self::assertSame($plugin, $createdPlugin);
    }

    public function testWillCreatePluginFromDeprecatedArrayConfiguration(): void
    {
        $plugin = $this->createMock(PluginInterface::class);

        $this->plugins
            ->expects(self::once())
            ->method('build')
            ->with('foo')
            ->willReturn($plugin);

        $createdPlugin = $this->factory->createFromArrayConfiguration(['name' => 'foo']);
        self::assertSame($plugin, $createdPlugin);
    }

    public function testWillCreatePluginFromArrayConfigurationWithOptions(): void
    {
        $plugin = $this->createMock(PluginInterface::class);

        $this->plugins
            ->expects(self::once())
            ->method('build')
            ->with('foo', ['bar' => 'baz'])
            ->willReturn($plugin);

        $createdPlugin = $this->factory->createFromArrayConfiguration(
            ['adapter' => 'foo', 'options' => ['bar' => 'baz']]
        );

        self::assertSame($plugin, $createdPlugin);
    }

    public function testWillCreatePlugin(): void
    {
        $plugin = $this->createMock(PluginInterface::class);

        $this->plugins
            ->expects(self::once())
            ->method('build')
            ->with('foo')
            ->willReturn($plugin);

        $createdPlugin = $this->factory->create('foo');
        self::assertSame($plugin, $createdPlugin);
    }

    public function testWillCreatePluginWithOptions(): void
    {
        $plugin = $this->createMock(PluginInterface::class);

        $this->plugins
            ->expects(self::once())
            ->method('build')
            ->with('foo', ['bar' => 'baz'])
            ->willReturn($plugin);

        $createdPlugin = $this->factory->create('foo', ['bar' => 'baz']);

        self::assertSame($plugin, $createdPlugin);
    }

    /**
     * @param array<mixed>  $invalidConfiguration
     * @psalm-param non-empty-string $expectedExceptionMessage
     * @dataProvider invalidConfigurations
     */
    public function testWillThrowInvalidArgumentExceptionWhenInvalidConfigurationIsPassedToConfigurationAssertion(
        array $invalidConfiguration,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->factory->assertValidConfigurationStructure($invalidConfiguration);
    }

    public function testWillAssertProperConfiguration(): void
    {
        $this->expectNotToPerformAssertions();
        $this->factory->assertValidConfigurationStructure([
            'adapter' => 'foo',
            'options' => ['bar' => 'baz'],
        ]);
    }
}
