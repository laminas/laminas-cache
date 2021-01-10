<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\CacheItemPool;

use DateInterval;
use DateTime;
use Laminas\Cache\Psr\CacheItemPool\CacheItem;
use Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheItemTest extends TestCase
{
    private $tz;

    protected function setUp(): void
    {
        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->tz);
    }

    public function testConstructorIsHit(): void
    {
        $item = new CacheItem('key', 'value', true);
        self::assertEquals('key', $item->getKey());
        self::assertEquals('value', $item->get());
        self::assertTrue($item->isHit());
    }

    public function testConstructorIsNotHit(): void
    {
        $item = new CacheItem('key', 'value', false);
        self::assertEquals('key', $item->getKey());
        self::assertNull($item->get());
        self::assertFalse($item->isHit());
    }

    public function testSet(): void
    {
        $item = new CacheItem('key', 'value', true);
        $return = $item->set('value2');
        self::assertEquals($item, $return);
        self::assertEquals('value2', $item->get());
    }

    public function testExpiresAtDateTime(): void
    {
        $item = new CacheItem('key', 'value', true);
        $dateTime = new DateTime('+5 seconds');
        $return = $item->expiresAt($dateTime);
        self::assertEquals($item, $return);
        self::assertEquals(5, $item->getTtl());
    }

    public function testExpireAtNull(): void
    {
        $item = new CacheItem('key', 'value', true);
        $return = $item->expiresAt(null);
        self::assertEquals($item, $return);
        self::assertNull($item->getTtl());
    }

    public function testExpireAtInvalidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $item = new CacheItem('key', 'value', true);
        $item->expiresAt('foo');
    }

    public function testExpiresAfterInt(): void
    {
        $item = new CacheItem('key', 'value', true);
        $return = $item->expiresAfter(3600);
        self::assertEquals($item, $return);
        self::assertEquals(3600, $item->getTtl());
    }

    public function testExpiresAfterInterval(): void
    {
        $item = new CacheItem('key', 'value', true);
        $interval = new DateInterval('PT1H');
        $return = $item->expiresAfter($interval);
        self::assertEquals($item, $return);
        self::assertEquals(3600, $item->getTtl());
    }

    public function testExpiresAfterNull(): void
    {
        $item = new CacheItem('key', 'value', true);
        $item->expiresAfter(null);
        self::assertNull($item->getTtl());
    }

    public function testExpiresAfterInvalidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $item = new CacheItem('key', 'value', true);
        $item->expiresAfter([]);
    }
}
