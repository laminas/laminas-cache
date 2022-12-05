<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\Pattern\StorageCapableInterface;
use Laminas\Cache\Storage\StorageInterface;

use function sprintf;

/**
 * @psalm-suppress MissingConstructor
 */
abstract class AbstractCommonStoragePatternTest extends AbstractCommonPatternTest
{
    /** @var StorageInterface */
    protected $storage;

    /** @var PatternInterface&StorageCapableInterface */
    protected $pattern;

    protected function setUp(): void
    {
        self::assertInstanceOf(
            StorageCapableInterface::class,
            $this->pattern,
            sprintf(
                'Internal pattern instance with implemented `%s` is needed for tests',
                StorageCapableInterface::class
            )
        );

        parent::setUp();
    }
}
