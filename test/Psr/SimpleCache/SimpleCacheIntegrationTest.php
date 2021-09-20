<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\SimpleCache;

use Laminas\Cache\Storage\Adapter\Apcu;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Storage\Adapter\AbstractSimpleCacheIntegrationTest;

final class SimpleCacheIntegrationTest extends AbstractSimpleCacheIntegrationTest
{
    protected function createStorage(): StorageInterface
    {
        $storage    = new Apcu();
        $serializer = new Serializer();
        $storage->addPlugin($serializer);

        return $storage;
    }
}
