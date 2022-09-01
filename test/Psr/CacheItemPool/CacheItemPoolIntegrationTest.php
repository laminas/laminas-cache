<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Laminas\Cache\Storage\Adapter\Apcu;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Storage\Adapter\AbstractCacheItemPoolIntegrationTest;

final class CacheItemPoolIntegrationTest extends AbstractCacheItemPoolIntegrationTest
{
    /**
     * Data provider for invalid keys.
     *
     * @return list<array{0:mixed}>
     */
    public static function invalidKeys(): array
    {
        return [
            ['{str'],
            ['rand{'],
            ['rand{str'],
            ['rand}str'],
            ['rand(str'],
            ['rand)str'],
            ['rand/str'],
            ['rand\\str'],
            ['rand@str'],
            ['rand:str'],
        ];
    }

    protected function createStorage(): StorageInterface
    {
        $storage    = new Apcu();
        $serializer = new Serializer();
        $storage->addPlugin($serializer);

        return $storage;
    }
}
