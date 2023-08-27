<?php

declare(strict_types=1);

namespace Laminas\Cache\Psr;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * @internal Internal clock, used in case no clock instance is passed to the PSR decorators.
 */
final class Clock implements ClockInterface
{
    public function __construct(
        private readonly DateTimeZone $timeZone,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(timezone: $this->timeZone);
    }
}
