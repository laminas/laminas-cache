<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Generator;
use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Exception\RuntimeException;
use Laminas\Cache\Service\StorageAdapterFactory;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Service\StoragePluginFactoryInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\Plugin\PluginInterface;
use Laminas\Cache\Storage\PluginAwareInterface;
use Laminas\Cache\Storage\StorageInterface;
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
    /** @var StorageAdapterFactory */
    private $factory;

    /** @var AdapterPluginManager&MockObject */
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
    public function storageConfigurations(): Generator
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
    public function pluginConfigurations(): Generator
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
    public function invalidConfigurations(): Generator
    {
        yield 'empty map' => [
            [],
            'Configuration must be a non-empty array',
        ];

        yield 'missing name' => [
            ['options' => []],
            'Configuration must contain a "name" key',
        ];

        yield 'empty name' => [
            ['name' => ''],
            'Storage "name" has to be a non-empty string',
        ];

        yield 'invalid options' => [
            ['name' => 'foo', 'options' => 'bar'],
            'Storage "options" must be an array with string keys',
        ];

        yield 'invalid plugin configuration' => [
            ['name' => 'foo', 'plugins' => ['bar']],
            'All plugin configurations are expected to be an array',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapters = $this->createMock(AdapterPluginManager::class);
        $this->plugins  = $this->createMock(StoragePluginFactoryInterface::class);
        $this->factory  = new StorageAdapterFactory($this->adapters, $this->plugins);
    }

    /**
     * @psalm-param non-empty-string $adapterName
     * @param array<string,mixed> $adapterConfiguration
     * @dataProvider storageConfigurations
     */
    public function testWillCreateStorageFromArrayConfiguration(
        string $adapterName,
        array $adapterConfiguration
    ): void {
        $adapterMock = $this->createMock(AbstractAdapter::class);
        $this->adapters
            ->expects(self::once())
            ->method('build')
            ->with($adapterName, $adapterConfiguration)
            ->willReturn($adapterMock);

        $adapter = $this->factory->createFromArrayConfiguration([
            'name'    => $adapterName,
            'options' => $adapterConfiguration,
        ]);

        self::assertSame($adapterMock, $adapter);
    }

    /**
     * @psalm-param list<PluginArrayConfigurationWithPriorityType> $plugins
     * @dataProvider pluginConfigurations
     */
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

        $consecutivePluginCreationArguments = $consecutivePluginAddArguments = [];
        foreach ($plugins as $pluginConfiguration) {
            $consecutivePluginCreationArguments[] = [$pluginConfiguration];
            $priority                             = $pluginConfiguration['priority']
                ?? StorageAdapterFactory::DEFAULT_PLUGIN_PRIORITY;
            $consecutivePluginAddArguments[]      = [$plugin, $priority];
        }

        $pluginCount = count($plugins);

        $this
            ->plugins
            ->expects(self::exactly($pluginCount))
            ->method('createFromArrayConfiguration')
            ->withConsecutive(...$consecutivePluginCreationArguments)
            ->willReturn($plugin);

        $adapterMock
            ->expects(self::exactly($pluginCount))
            ->method('hasPlugin')
            ->with($plugin)
            ->willReturn(false);

        $adapterMock
            ->expects(self::exactly($pluginCount))
            ->method('addPlugin')
            ->withConsecutive(...$consecutivePluginAddArguments);

        $this->factory->create('foo', [], $plugins);
    }

    public function testThrowsExceptionWhenStorageIsNotPluginAwareButPluginsConfigurationIsProvided(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $this->adapters
            ->expects(self::once())
            ->method('build')
            ->willReturn($storage);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($this->createPluginAwareInterfaceIsMissingExceptionMessage());
        $this->factory->create('foo', [], [['name' => 'bar']]);
    }

    /**
     * @param array<mixed>  $invalidConfiguration
     * @psalm-param non-empty-string $expectedExceptionMessage
     * @dataProvider invalidConfigurations
     */
    public function testWillThrowInvalidArgumentExceptionWhenInvalidConfigurationsWherePassedToConfigurationAssertion(
        array $invalidConfiguration,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->factory->assertValidConfigurationStructure($invalidConfiguration);
    }

    public function testThrowsExceptionWhenInvalidPluginConfigurationIsPassedToConfigurationAssertion(): void
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Plugin configuration for adapter "foo" is invalid: ERROR FROM PLUGIN CONFIGURATION ASSERTION'
        );

        $this->plugins
            ->expects(self::once())
            ->method('assertValidConfigurationStructure')
            ->with(['name' => ''])
            ->willThrowException(new InvalidArgumentException('ERROR FROM PLUGIN CONFIGURATION ASSERTION'));

        $this->factory->assertValidConfigurationStructure([
            'name'    => 'foo',
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
            ->expects(self::once())
            ->method('assertValidConfigurationStructure');

        $this->factory->assertValidConfigurationStructure([
            'name'    => 'bar',
            'plugins' => [
                ['name' => 'baz', 'priority' => true],
            ],
        ]);
    }

    public function testWillAssertProperConfiguration(): void
    {
        $this->expectNotToPerformAssertions();
        $this->factory->assertValidConfigurationStructure([
            'name'    => 'foo',
            'options' => ['bar' => 'baz'],
        ]);
    }
}
