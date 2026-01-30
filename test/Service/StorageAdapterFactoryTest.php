<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Generator;
use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Exception\RuntimeException;
use Laminas\Cache\Service\StorageAdapterFactory;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StoragePluginFactoryInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginAwareInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function count;
use function sprintf;

/**
 * @see StorageAdapterFactoryInterface
 *
 * @psalm-import-type PluginArrayConfigurationWithPriorityType from StorageAdapterFactoryInterface
 */
final class StorageAdapterFactoryTest extends TestCase
{
    private StorageAdapterFactory $factory;

    /** @var PluginManagerInterface&MockObject */
    private $adapters;

    /** @var StoragePluginFactoryInterface&MockObject */
    private $plugins;

    private function createPluginAwareInterfaceIsMissingExceptionMessage(): string
    {
        return sprintf(
            '\'%s\' and therefore can\'t handle plugins',
            PluginAwareInterface::class
        );
    }

    /**
     * @return Generator<non-empty-string,array{0:non-empty-string,1:array<string,mixed>}>
     */
    public static function storageConfigurations(): Generator
    {
        yield 'Storage without options' => [
            'Foo',
            [],
        ];

        yield 'Storage with options' => [
            'Foo',
            ['ttl' => 1],
        ];
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:list<PluginArrayConfigurationWithPriorityType>}>
     */
    public static function pluginConfigurations(): Generator
    {
        yield 'list of plugin configurations' => [
            [
                ['name' => 'Foo'],
                ['name' => 'Bar'],
                ['name' => 'Baz'],
            ],
        ];
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:array<mixed>,1:non-empty-string}>
     */
    public static function invalidConfigurations(): Generator
    {
        yield 'empty map' => [
            [],
            'Configuration must be a non-empty array',
        ];

        yield 'missing name' => [
            ['options' => []],
            'Configuration must contain a "adapter" key',
        ];

        yield 'empty name' => [
            ['adapter' => ''],
            'Storage "adapter" has to be a non-empty string',
        ];

        yield 'invalid options' => [
            ['adapter' => 'foo', 'options' => 'bar'],
            'Storage "options" must be an array with string keys',
        ];

        yield 'invalid plugin configuration' => [
            ['adapter' => 'foo', 'plugins' => ['bar']],
            'All plugin configurations are expected to be an array',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapters = $this->createMock(PluginManagerInterface::class);
        $this->plugins  = $this->createMock(StoragePluginFactoryInterface::class);
        $this->factory  = new StorageAdapterFactory($this->adapters, $this->plugins);
    }

    /**
     * @psalm-param non-empty-string $adapterName
     * @param array<string,mixed> $adapterConfiguration
     */
    #[DataProvider('storageConfigurations')]
    public function testWillCreateStorageFromArrayConfiguration(
        string $adapterName,
        array $adapterConfiguration
    ): void {
        $adapterMock = $this->createMock(AbstractAdapter::class);
        $this->adapters
            ->expects($this->once())
            ->method('build')
            ->with($adapterName, $adapterConfiguration)
            ->willReturn($adapterMock);

        $adapter = $this->factory->createFromArrayConfiguration([
            'adapter' => $adapterName,
            'options' => $adapterConfiguration,
        ]);

        self::assertSame($adapterMock, $adapter);
    }

    /**
     * @psalm-param list<PluginArrayConfigurationWithPriorityType> $plugins
     */
    #[DataProvider('pluginConfigurations')]
    public function testWillCreateAdapterAndAttachesPlugins(array $plugins): void
    {
        $adapterMock = $this->createMock(AbstractAdapter::class);
        $adapterName = 'foo';
        $this
            ->adapters
            ->method('build')
            ->with($adapterName)
            ->willReturn($adapterMock);

        $plugin = $this->createMock(PluginInterface::class);

        $pluginCount = count($plugins);

        $createFromArrayInvokedCount = self::exactly($pluginCount);
        $this
            ->plugins
            ->expects($createFromArrayInvokedCount)
            ->method('createFromArrayConfiguration')
            ->with(self::callback(static function ($arg) use ($plugins, $createFromArrayInvokedCount): bool {
                $invocation = $createFromArrayInvokedCount->numberOfInvocations() - 1;
                self::assertSame($plugins[$invocation], $arg);
                return true;
            }))
            ->willReturn($plugin);

        $adapterMock
            ->expects(self::exactly($pluginCount))
            ->method('hasPlugin')
            ->with($plugin)
            ->willReturn(false);

        $addPluginInvokedCount = self::exactly($pluginCount);
        $adapterMock
            ->expects($addPluginInvokedCount)
            ->method('addPlugin')
            ->with(self::callback(static function (...$args) use ($plugins, $addPluginInvokedCount, $plugin) {
                $invocation = $addPluginInvokedCount->numberOfInvocations() - 1;
                $config     = $plugins[$invocation];
                $priority   = $config['priority'] ?? StorageAdapterFactory::DEFAULT_PLUGIN_PRIORITY;
                $expect     = [$plugin, $priority];
                self::assertSame($expect, $args);
                return true;
            }));

        $this->factory->create('foo', [], $plugins);
    }

    public function testThrowsExceptionWhenStorageIsNotPluginAwareButPluginsConfigurationIsProvided(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $this->adapters
            ->expects($this->once())
            ->method('build')
            ->willReturn($storage);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($this->createPluginAwareInterfaceIsMissingExceptionMessage());
        $this->factory->create('foo', [], [['name' => 'bar']]);
    }

    /**
     * @param array<mixed>  $invalidConfiguration
     * @psalm-param non-empty-string $expectedExceptionMessage
     */
    #[DataProvider('invalidConfigurations')]
    public function testWillThrowInvalidArgumentExceptionWhenInvalidConfigurationsWherePassedToConfigurationAssertion(
        array $invalidConfiguration,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        /** @psalm-suppress TypeDoesNotContainType */
        $this->factory->assertValidConfigurationStructure($invalidConfiguration);
    }

    public function testThrowsExceptionWhenInvalidPluginConfigurationIsPassedToConfigurationAssertion(): void
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Plugin configuration for adapter "foo" is invalid: ERROR FROM PLUGIN CONFIGURATION ASSERTION'
        );

        $this->plugins
            ->expects($this->once())
            ->method('assertValidConfigurationStructure')
            ->with(['name' => ''])
            ->willThrowException(new InvalidArgumentException('ERROR FROM PLUGIN CONFIGURATION ASSERTION'));

        $this->factory->assertValidConfigurationStructure([
            'adapter' => 'foo',
            'plugins' => [
                ['name' => ''],
            ],
        ]);
    }

    public function testWillThrowInvalidArgumentExceptionWhenPluginPriorityIsNotInteger(): void
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Plugin configuration for adapter "bar" is invalid: Plugin priority has to be integer'
        );

        $this->plugins
            ->expects($this->once())
            ->method('assertValidConfigurationStructure');

        $this->factory->assertValidConfigurationStructure([
            'adapter' => 'bar',
            'plugins' => [
                ['name' => 'baz', 'priority' => true],
            ],
        ]);
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
