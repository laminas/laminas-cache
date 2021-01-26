<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Pattern\TestAsset\TestClassCache;

use function implode;
use function ob_get_clean;
use function ob_implicit_flush;
use function ob_start;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\ClassCache<extended>
 */
class ClassCacheTest extends AbstractCommonPatternTest
{
    /** @var StorageInterface */
    protected $storage;

    /** @var Cache\Pattern\PatternOptions */
    private $options;

    public function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0,
        ]);
        $this->options = new Cache\Pattern\PatternOptions([
            'class'   => TestClassCache::class,
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\ClassCache();
        $this->pattern->setOptions($this->options);

        parent::setUp();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPatternNamesProvider()
    {
        return [
            'lowercase' => ['class'],
            'ucfirst'   => ['Class'],
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

    public function testGenerateKey(): void
    {
        $args = ['arg1', 2, 3.33, null];

        $generatedKey = $this->pattern->generateKey('emptyMethod', $args);
        $usedKey      = null;
        $this->options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params  = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->pattern->call('emptyMethod', $args);
        self::assertEquals($generatedKey, $usedKey);
    }

    protected function executeMethodAndMakeAssertions(string $method, array $args): void
    {
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';

        // first call - not cached
        $firstCounter = TestClassCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = $this->pattern->{$method}(...$args);
        $data   = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = $this->pattern->{$method}(...$args);
        $data   = ob_get_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        if ($this->options->getCacheOutput()) {
            self::assertEquals($outputSpec . $firstCounter, $data);
        } else {
            self::assertEquals('', $data);
        }
    }
}
