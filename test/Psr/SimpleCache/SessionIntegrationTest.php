<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\SimpleCache;

use Cache\IntegrationTests\SimpleCacheTest;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\StorageFactory;
use Laminas\Session\Container as SessionContainer;

class SessionIntegrationTest extends SimpleCacheTest
{
    public function setUp()
    {
        if (! class_exists(SessionContainer::class)) {
            $this->markTestSkipped('Install laminas-session to enable this test');
        }

        $_SESSION = [];
        SessionContainer::setDefaultManager(null);

        $this->skippedTests['testSetTtl'] = 'Session adapter does not honor TTL';
        $this->skippedTests['testSetMultipleTtl'] = 'Session adapter does not honor TTL';
        $this->skippedTests['testObjectDoesNotChangeInCache'] =
            'Session adapter stores objects in memory; so change in references is possible';

        parent::setUp();
    }

    public function tearDown()
    {
        if (! class_exists(SessionContainer::class)) {
            return;
        }

        $_SESSION = [];
        SessionContainer::setDefaultManager(null);

        parent::tearDown();
    }

    public function createSimpleCache()
    {
        $sessionContainer = new SessionContainer('Default');
        $storage = StorageFactory::adapterfactory('session', [
            'session_container' => $sessionContainer,
        ]);
        return new SimpleCacheDecorator($storage);
    }
}
