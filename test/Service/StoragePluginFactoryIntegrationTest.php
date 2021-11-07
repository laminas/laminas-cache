<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Service;

use Laminas\Cache\Service\StoragePluginFactory;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\Storage\PluginManager;
use Laminas\Serializer\Adapter\Json;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

final class StoragePluginFactoryIntegrationTest extends TestCase
{
    /** @var StoragePluginFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new StoragePluginFactory(new PluginManager(new ServiceManager()));
    }

    public function testWillCreatePluginWithOptions(): void
    {
        $plugin  = $this->factory->create(Serializer::class, ['serializer' => 'json']);
        $options = $plugin->getOptions();
        self::assertInstanceOf(Json::class, $options->getSerializer());
    }
}
