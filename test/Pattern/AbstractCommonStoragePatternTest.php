<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\Pattern\StorageCapableInterface;
use Laminas\Cache\Storage\StorageInterface;

use function sprintf;

abstract class AbstractCommonStoragePatternTest extends AbstractCommonPatternTest
{
    /** @var StorageInterface */
    protected $storage;

    /** @var StorageCapableInterface */
    protected $pattern;

    /** @var PatternOptions */
    protected $options;

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

        self::assertInstanceOf(
            PatternOptions::class,
            $this->options,
            'Pattern options are missing'
        );

        parent::setUp();
    }

    public function testGetStorageReturnsStorage(): void
    {
        self::assertSame($this->storage, $this->pattern->getStorage());
    }
}
