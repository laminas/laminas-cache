<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\CacheItemPool;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Laminas\Cache\Psr\CacheItemPool\CacheItem;
use Laminas\Cache\Psr\CacheItemPool\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

use function date_default_timezone_get;
use function date_default_timezone_set;

class CacheItemTest extends TestCase
{
    /** @var non-empty-string */
    private string $tz;

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
        $item   = new CacheItem('key', 'value', true);
        $return = $item->set('value2');
        self::assertEquals($item, $return);
        self::assertEquals('value2', $item->get());
    }

    public function testExpiresAtDateTime(): void
    {
        $item     = new CacheItem('key', 'value', true);
        $dateTime = new DateTime('+5 seconds');
        $return   = $item->expiresAt($dateTime);
        self::assertEquals($item, $return);
        self::assertEquals(5, $item->getTtl());
    }

    public function testExpireAtNull(): void
    {
        $item   = new CacheItem('key', 'value', true);
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
        $item   = new CacheItem('key', 'value', true);
        $return = $item->expiresAfter(3600);
        self::assertEquals($item, $return);
        self::assertEquals(3600, $item->getTtl());
    }

    public function testExpiresAfterInterval(): void
    {
        $item     = new CacheItem('key', 'value', true);
        $interval = new DateInterval('PT1H');
        $return   = $item->expiresAfter($interval);
        self::assertEquals($item, $return);
        self::assertEquals(3600, $item->getTtl());
    }

    public function testExpiresAfterNull(): void
    {
        $item = new CacheItem('key', 'value', true);
        $item->expiresAfter(null);
        self::assertNull($item->getTtl());
    }

    public function testExpiresAfterStartsExpiringAfterMethodCall(): void
    {
        $now              = new DateTimeImmutable();
        $nowPlusOneSecond = $now->add(DateInterval::createFromDateString('1 second'));

        $clock = $this->createMock(ClockInterface::class);
        $clock
            ->expects(self::exactly(2))
            ->method('now')
            ->willReturnOnConsecutiveCalls($now, $nowPlusOneSecond);

        $item = new CacheItem('key', 'value', false, $clock);
        $item = $item->expiresAfter(10);
        self::assertEquals(9, $item->getTtl());
    }

    public function testClockProvidedDoesNotContainUTCTimeZone(): void
    {
        $item = new CacheItem(
            'foo',
            null,
            false,
            new class implements ClockInterface
            {
                public function now(): DateTimeImmutable
                {
                    return new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin'));
                }
            }
        );

        $interval = DateInterval::createFromDateString('1 hour');
        $item->expiresAfter($interval);

        self::assertEquals(3600, $item->getTtl());

        $inOneHour = (new DateTimeImmutable('now', new DateTimeZone('America/Vancouver')))->add($interval);
        $item->expiresAt($inOneHour);

        self::assertEquals(3600, $item->getTtl());
    }
}
