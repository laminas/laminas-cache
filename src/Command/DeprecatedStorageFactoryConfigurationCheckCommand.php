<?php

declare(strict_types=1);

namespace Laminas\Cache\Command;

use ArrayAccess;
use Laminas\Cache\Service\DeprecatedSchemaDetectorInterface;
use Laminas\Cache\Service\StorageCacheAbstractServiceFactory;
use Laminas\Cache\Service\StorageCacheFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function implode;
use function is_string;
use function sprintf;

/**
 * @internal
 *
 * phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSame
 */
final class DeprecatedStorageFactoryConfigurationCheckCommand extends Command
{
    public const NAME         = 'laminas-cache:deprecation:check-storage-factory-config';
    private const DESCRIPTION = <<<EOT
        Helps to detect deprecated cache configurations which are used to create the storage adapter.
    EOT;

    private const CACHES_CONFIGURATION_KEY = StorageCacheAbstractServiceFactory::CACHES_CONFIGURATION_KEY;
    private const CACHE_CONFIGURATION_KEY = StorageCacheFactory::CACHE_CONFIGURATION_KEY;
    private const MESSAGE_CACHE_CONFIGURATIONS_ARE_VALID
        = '<info>The project configuration does not contain deprecated storage factory configurations.</info>';
    private const MESSAGE_PROJECT_DOES_NOT_CONTAIN_CACHE_CONFIGURATIONS
        = '<info>Project configuration does not contain deprecated configurations.';
    private const MESSAGE_PROJECT_CONFIGURATION_CONTAINS_INVALID_CACHES_CONFIGURATION
        = 'One or more configurations of the configured caches are deprecated.'
        . ' Please normalize the `%s` configuration, it contains deprecated configuration(s)';
    private const MESSAGE_PROJECT_CONFIGURATION_CONTAINS_INVALID_CACHE_CONFIGURATION
        = 'Please normalize the `%s` configuration as it contains deprecated configuration.';
    private const MESSAGE_SCHEMA_DOCUMENTATION_MESSAGE = 'The normalized schema can be found at'
    . ' https://docs.laminas.dev/laminas-cache/storage/adapter/#quick-start';

    /** @var ArrayAccess<string,mixed> */
    private $projectConfiguration;

    /** @var DeprecatedSchemaDetectorInterface */
    private $deprecatedSchemaDetector;

    public function __construct(
        ArrayAccess $projectConfiguration,
        DeprecatedSchemaDetectorInterface $deprecatedSchemaDetector
    ) {
        parent::__construct(self::NAME);
        $this->projectConfiguration     = $projectConfiguration;
        $this->deprecatedSchemaDetector = $deprecatedSchemaDetector;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->projectConfigurationContainsAnyCacheConfiguration()) {
            $output->writeln(self::MESSAGE_PROJECT_DOES_NOT_CONTAIN_CACHE_CONFIGURATIONS);
            return self::SUCCESS;
        }

        $output->writeln(
            sprintf(
                'Scanning `%s` configuration key for deprecated configurations...',
                self::CACHES_CONFIGURATION_KEY
            )
        );
        $caches = $this->projectConfiguration[self::CACHES_CONFIGURATION_KEY] ?? [];

        $invalidCaches = [];
        foreach ($caches as $cacheIdentifier => $configuration) {
            if (! $this->deprecatedSchemaDetector->isDeprecatedStorageFactorySchema($configuration)) {
                continue;
            }
            assert(is_string($cacheIdentifier));

            $invalidCaches[] = $cacheIdentifier;
        }

        $cacheConfiguration             = $this->projectConfiguration[self::CACHE_CONFIGURATION_KEY] ?? [];
        $cacheConfigurationIsDeprecated = false;
        if ($cacheConfiguration !== []) {
            $cacheConfigurationIsDeprecated = $this->deprecatedSchemaDetector->isDeprecatedStorageFactorySchema(
                $cacheConfiguration
            );
        }

        if ($invalidCaches === [] && ! $cacheConfigurationIsDeprecated) {
            $output->writeln(self::MESSAGE_CACHE_CONFIGURATIONS_ARE_VALID);
            return self::SUCCESS;
        }

        if ($invalidCaches !== []) {
            $output->writeln(
                sprintf(
                    '<error>%s: "%s".</error>',
                    sprintf(
                        self::MESSAGE_PROJECT_CONFIGURATION_CONTAINS_INVALID_CACHES_CONFIGURATION,
                        self::CACHES_CONFIGURATION_KEY
                    ),
                    implode('", "', $invalidCaches)
                )
            );
        }

        if ($cacheConfigurationIsDeprecated) {
            $output->writeln(
                sprintf(
                    '<error>%s</error>',
                    sprintf(
                        self::MESSAGE_PROJECT_CONFIGURATION_CONTAINS_INVALID_CACHE_CONFIGURATION,
                        self::CACHE_CONFIGURATION_KEY
                    )
                )
            );
        }

        $output->writeln(sprintf('<info>%s</info>', self::MESSAGE_SCHEMA_DOCUMENTATION_MESSAGE));

        return self::FAILURE;
    }

    private function projectConfigurationContainsAnyCacheConfiguration(): bool
    {
        $cache  = $this->projectConfiguration[self::CACHE_CONFIGURATION_KEY] ?? [];
        $caches = $this->projectConfiguration[self::CACHES_CONFIGURATION_KEY] ?? [];

        return $cache !== [] || $caches !== [];
    }
}
