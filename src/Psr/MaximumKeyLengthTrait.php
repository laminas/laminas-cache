<?php

declare(strict_types=1);

namespace Laminas\Cache\Psr;

use Laminas\Cache\Psr\SimpleCache\SimpleCacheInvalidArgumentException;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;

use function get_class;
use function min;
use function preg_match;
use function sprintf;

/**
 * Provides memoizing of maximum key length for a storage adapter.
 *
 * @internal
 */
trait MaximumKeyLengthTrait
{
    /**
     * PCRE runs into a compilation error if the quantifier exceeds this limit
     *
     * @internal
     *
     * @readonly
     * @var positive-int
     */
    public static $pcreMaximumQuantifierLength = 65535;

    /**
     * @var int
     * @psalm-var 0|positive-int
     */
    private $maximumKeyLength;

    private function memoizeMaximumKeyLengthCapability(StorageInterface $storage, Capabilities $capabilities): void
    {
        $maximumKeyLength = $capabilities->getMaxKeyLength();

        if ($maximumKeyLength === Capabilities::UNLIMITED_KEY_LENGTH) {
            $this->maximumKeyLength = Capabilities::UNLIMITED_KEY_LENGTH;
            return;
        }

        if ($maximumKeyLength === Capabilities::UNKNOWN_KEY_LENGTH) {
            // For backward compatibility, assume adapters which do not provide a maximum key length do support 64 chars
            $maximumKeyLength = 64;
        }

        if ($maximumKeyLength < 64) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'The storage adapter "%s" does not fulfill the minimum requirements for PSR-6/PSR-16:'
                . ' The maximum key length capability must allow at least 64 characters.',
                get_class($storage)
            ));
        }

        /** @psalm-suppress PropertyTypeCoercion The result of this will always be > 0 */
        $this->maximumKeyLength = min($maximumKeyLength, self::$pcreMaximumQuantifierLength - 1);
    }

    private function exceedsMaximumKeyLength(string $key): bool
    {
        return $this->maximumKeyLength !== Capabilities::UNLIMITED_KEY_LENGTH
            && preg_match('/^.{' . ($this->maximumKeyLength + 1) . ',}/u', $key);
    }
}
