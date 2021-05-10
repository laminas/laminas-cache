<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Generator;
use Laminas\Cache\Service\DeprecatedSchemaDetector;
use PHPUnit\Framework\TestCase;

final class DeprecatedSchemaDetectorTest extends TestCase
{
    /**
     * @var DeprecatedSchemaDetector
     */
    private $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new DeprecatedSchemaDetector();
    }

    public function deprecatedStorageConfigurationSchemas(): Generator
    {
        yield 'adapter is not the alias or class-name of the adapter' => [
            [
                'adapter' => ['name' => 'adapterName'],
            ],
        ];

        yield 'plugins contain plugin name as key and options as value' => [
            [
                'adapter' => 'adapterName',
                'plugins' => ['pluginName' => ['option' => 'value']],
            ],
        ];

        yield 'plugins are a list of plugin names' => [
            [
                'adapter' => 'adapterName',
                'plugins' => ['pluginName'],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $schema
     * @dataProvider deprecatedStorageConfigurationSchemas
     */
    public function testWillProperlyDetectAllInvalidConfigurationSchemas(array $schema): void
    {
        self::assertTrue($this->detector->isDeprecatedStorageFactorySchema($schema));
    }
}
