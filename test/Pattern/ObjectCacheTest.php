<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Pattern\ObjectCache;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Pattern\TestAsset\TestObjectCache;

use function implode;
use function ob_end_clean;
use function ob_get_contents;
use function ob_implicit_flush;
use function ob_start;

/**
 * @group      Laminas_Cache
 * @template-extends AbstractCommonStoragePatternTest<ObjectCache>
 */
class ObjectCacheTest extends AbstractCommonStoragePatternTest
{
    /** @var PatternOptions */
    protected $options;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->options = new Cache\Pattern\PatternOptions([
            'object' => new TestObjectCache(),
        ]);
        $this->pattern = new ObjectCache($this->storage, $this->options);

        parent::setUp();
    }

    public function getCommonPatternNamesProvider(): array
    {
        return [
            'lowercase' => ['object'],
            'ucfirst'   => ['Object'],
        ];
    }

    public function testCallEnabledCacheOutputByDefault(): void
    {
        $this->executeMethodAndMakeAssertions(
            'bar',
            ['testCallEnabledCacheOutputByDefault', 'arg2']
        );
    }

    public function testCallDisabledCacheOutput(): void
    {
        $this->options->setCacheOutput(false);
        $this->executeMethodAndMakeAssertions(
            'bar',
            ['testCallDisabledCacheOutput', 'arg2']
        );
    }

    public function testCallInvoke(): void
    {
        $this->options->setCacheOutput(false);
        $this->executeMethodAndMakeAssertions('__invoke', ['arg1', 'arg2']);
    }

    public function testGenerateKey(): void
    {
        $args = ['arg1', 2, 3.33, null];

        $generatedKey = $this->pattern->generateKey('emptyMethod', $args);

        $this->storage
            ->expects(self::once())
            ->method('getItem')
            ->with($generatedKey, null)
            ->willReturn(null);

        $this->storage
            ->expects(self::once())
            ->method('setItem')
            ->with($generatedKey, self::anything())
            ->willReturn(true);

        $this->pattern->call('emptyMethod', $args);
    }

    public function testSetProperty(): void
    {
        $this->pattern->property = 'testSetProperty';
        self::assertEquals('testSetProperty', $this->options->getObject()->property);
    }

    public function testGetProperty(): void
    {
        self::assertEquals($this->options->getObject()->property, $this->pattern->property);
    }

    public function testIssetProperty(): void
    {
        self::assertTrue(isset($this->pattern->property));
        self::assertFalse(isset($this->pattern->unknownProperty));
    }

    public function testUnsetProperty(): void
    {
        unset($this->pattern->property);
        self::assertFalse(isset($this->pattern->property));
    }

    /**
     * @group 7039
     */
    public function testEmptyObjectKeys(): void
    {
        $this->options->setObjectKey('0');
        self::assertSame('0', $this->options->getObjectKey(), "Can't set string '0' as object key");

        $this->options->setObjectKey('');
        self::assertSame('', $this->options->getObjectKey(), "Can't set an empty string as object key");

        $this->options->setObjectKey(null);
        self::assertSame($this->options->getObject()::class, $this->options->getObjectKey());
    }

    /**
     * @param array<array-key,string>  $args
     */
    protected function executeMethodAndMakeAssertions(string $method, array $args): void
    {
        $options     = $this->pattern->getOptions();
        $cacheOutput = $options->getCacheOutput();
        $imploded    = implode(', ', $args);
        $returnSpec  = 'foobar_return(' . $imploded . ') : ';
        $outputSpec  = 'foobar_output(' . $imploded . ') : ';
        $callback    = [$this->pattern, $method];

        // first call - not cached
        $firstCounter = TestObjectCache::$fooCounter + 1;

        $expectedKey = $this->pattern->generateKey($method, $args);
        $this->storage
            ->expects(self::exactly(2))
            ->method('getItem')
            ->with($expectedKey, null)
            ->willReturnCallback(
                function (
                    string $key,
                    bool|null &$success = null
                ) use (
                    $returnSpec,
                    $outputSpec,
                    $firstCounter,
                    $cacheOutput,
                ): ?array {
                    static $called = false;
                    if ($called === true) {
                        $success = true;

                        $cached = [$returnSpec . $firstCounter];
                        if ($cacheOutput) {
                            $cached[] = $outputSpec . $firstCounter;
                        }

                        return $cached;
                    }

                    $called = true;
                    return null;
                }
            );

        ob_start();
        ob_implicit_flush(false);

        $return = $callback(...$args);
        $data   = ob_get_contents();
        ob_end_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(false);

        $return = $callback(...$args);
        $data   = ob_get_contents();
        ob_end_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        if ($cacheOutput) {
            self::assertEquals($outputSpec . $firstCounter, $data);
        } else {
            self::assertEquals('', $data);
        }
    }
}
