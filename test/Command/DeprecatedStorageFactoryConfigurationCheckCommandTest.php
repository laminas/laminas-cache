<?php
/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace LaminasTest\Cache\Command;

use ArrayObject;
use Laminas\Cache\Command\DeprecatedStorageFactoryConfigurationCheckCommand;
use Laminas\Cache\Service\DeprecatedSchemaDetectorInterface;
use Laminas\Cache\Service\StorageCacheAbstractServiceFactory;
use Laminas\Cache\Service\StorageCacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DeprecatedStorageFactoryConfigurationCheckCommandTest extends TestCase
{
    /**
     * @var DeprecatedSchemaDetectorInterface&MockObject
     */
    private $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = $this->createMock(DeprecatedSchemaDetectorInterface::class);
    }

    public function testWillExitEarlyWhenProjectDoesNotHaveCacheConfigurations(): void
    {
        $config = new ArrayObject([]);
        $command = new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $this->detector
        );

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        self::assertEquals(Command::SUCCESS, $command->run($input, $output));
    }

    public function testWillExitEarlyWhenProjectContainsCacheConfigurationButItsEmpty(): void
    {
        $config = new ArrayObject([
            StorageCacheFactory::CACHE_CONFIGURATION_KEY => [],
        ]);
        $command = new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $this->detector
        );

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        self::assertEquals(Command::SUCCESS, $command->run($input, $output));
    }

    public function testWillExitEarlyWhenProjectContainsCachesConfigurationButItsEmpty(): void
    {
        $config = new ArrayObject([
            StorageCacheAbstractServiceFactory::CACHES_CONFIGURATION_KEY => [],
        ]);
        $command = new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $this->detector
        );

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        self::assertEquals(Command::SUCCESS, $command->run($input, $output));
    }

    public function testWillDetectInvalidCachesConfiguration(): void
    {
        $config = new ArrayObject([
            StorageCacheAbstractServiceFactory::CACHES_CONFIGURATION_KEY => [
                'foo' => [],
            ],
        ]);
        $command = new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $this->detector
        );

        $this->detector
            ->expects(self::once())
            ->method('isDeprecatedStorageFactorySchema')
            ->with([])
            ->willReturn(true);

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        self::assertEquals(Command::FAILURE, $command->run($input, $output));
    }

    public function testWillDetectMultipleInvalidCachesConfiguration(): void
    {
        $config = new ArrayObject([
            StorageCacheAbstractServiceFactory::CACHES_CONFIGURATION_KEY => [
                'foo' => ['key' => 'foo'],
                'bar' => ['key' => 'bar'],
                'baz' => ['key' => 'baz'],
            ],
        ]);
        $command = new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $this->detector
        );

        $this->detector
            ->expects(self::exactly(3))
            ->method('isDeprecatedStorageFactorySchema')
            ->withConsecutive([['key' => 'foo']], [['key' => 'bar']], [['key' => 'baz']])
            ->willReturnOnConsecutiveCalls(true, false, true);

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);


        $output
            ->expects(self::exactly(2))
            ->method('writeln')
            ->withConsecutive([], [
                self::callback(static function (string $message): bool {
                    self::assertStringContainsString('Please normalize the `caches` configuration', $message);
                    self::assertStringContainsString('"foo", "baz"', $message);
                    return true;
                })
            ]);

        self::assertEquals(Command::FAILURE, $command->run($input, $output));
    }

    public function testWillDetectInvalidCacheConfiguration(): void
    {
        $config = new ArrayObject([
            StorageCacheFactory::CACHE_CONFIGURATION_KEY => [
                'foo' => 'bar',
            ],
        ]);
        $command = new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $this->detector
        );

        $this->detector
            ->expects(self::once())
            ->method('isDeprecatedStorageFactorySchema')
            ->with(['foo' => 'bar'])
            ->willReturn(true);

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output
            ->expects(self::exactly(2))
            ->method('writeln')
            ->withConsecutive([], [
                self::callback(static function (string $message): bool {
                    self::assertStringContainsString('Please normalize the `cache` configuration', $message);
                    return true;
                })
            ]);

        self::assertEquals(Command::FAILURE, $command->run($input, $output));
    }

    public function testWontDetectNormalizedConfiguration(): void
    {
        $config = new ArrayObject([
            StorageCacheFactory::CACHE_CONFIGURATION_KEY => [
                'foo' => 'bar',
            ],
            StorageCacheAbstractServiceFactory::CACHES_CONFIGURATION_KEY => [
                'foo' => ['bar' => 'baz'],
            ],
        ]);
        $command = new DeprecatedStorageFactoryConfigurationCheckCommand(
            $config,
            $this->detector
        );

        $this->detector
            ->expects(self::exactly(2))
            ->method('isDeprecatedStorageFactorySchema')
            ->withConsecutive([['bar' => 'baz']], [['foo' => 'bar']])
            ->willReturn(false);

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output
            ->expects(self::exactly(2))
            ->method('writeln')
            ->withConsecutive([], [
                self::callback(static function (string $message): bool {
                    self::assertStringContainsString(
                        'The project configuration does not contain deprecated',
                        $message
                    );
                    return true;
                })
            ]);

        self::assertEquals(Command::SUCCESS, $command->run($input, $output));
    }
}
