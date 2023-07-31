<?php

namespace Laminas\Cache\Psr\CacheItemPool;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Clock\ClockInterface;

use function gettype;
use function is_int;
use function sprintf;

/**
 * @internal The cache item should only be used by this component. To create one or more new cache item, use
 *           {@see \Psr\Cache\CacheItemPoolInterface::getItem()} or {@see \Psr\Cache\CacheItemPoolInterface::getItems()}
 *           instead. These methods will provide one or more {@see CacheItemInterface} instances which can be modified
 *           by using the methods declared in the interface.
 */
final class CacheItem implements CacheItemInterface
{
    /**
     * Cache value
     *
     * @var mixed|null
     */
    private $value;

    /**
     * Timestamp item will expire at if expiresAt() called, null otherwise
     */
    private ?int $expiration = null;

    private ClockInterface $clock;

    public function __construct(
        private string $key,
        mixed $value,
        /**
         * True if the cache item lookup resulted in a cache hit or if they item is deferred or successfully saved
         */
        private bool $isHit,
        ?ClockInterface $clock = null
    ) {
        $this->value = $isHit ? $value : null;
        $this->isHit = $isHit;
        $clock     ??= new class implements ClockInterface
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
        $this->clock = $clock;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        if (! $this->isHit) {
            return false;
        }
        $ttl = $this->getTtl();
        return $ttl === null || $ttl > 0;
    }

    /**
     * Sets isHit value
     *
     * This function is called by CacheItemPoolDecorator::saveDeferred() and is not intended for use by other calling
     * code.
     */
    public function setIsHit(bool $isHit): self
    {
        $this->isHit = $isHit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value): CacheItemInterface
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration): CacheItemInterface
    {
        if (! ($expiration === null || $expiration instanceof DateTimeInterface)) {
            throw new InvalidArgumentException('$expiration must be null or an instance of DateTimeInterface');
        }

        $this->expiration = $expiration instanceof DateTimeInterface ? $expiration->getTimestamp() : null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time): CacheItemInterface
    {
        if ($time === null) {
            return $this->expiresAt(null);
        }

        if (is_int($time)) {
            $interval = DateInterval::createFromDateString(sprintf('%d seconds', $time));
            if ($interval === false) {
                throw new InvalidArgumentException(sprintf('Provided TTL "%d" is not supported.', $time));
            }

            $time = $interval;
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType Until we do have native type-hints we should keep verifying this. */
        if ($time instanceof DateInterval) {
            $now = $this->clock->now();
            return $this->expiresAt($now->add($time));
        }

        throw new InvalidArgumentException(sprintf('Invalid $time "%s"', gettype($time)));
    }

    /**
     * Returns number of seconds until item expires
     *
     * If NULL, the pool should use the default TTL for the storage adapter. If <= 0, the item has expired.
     */
    public function getTtl(): ?int
    {
        if ($this->expiration === null) {
            return null;
        }

        $now = $this->clock->now();

        return $this->expiration - $now->getTimestamp();
    }
}
