<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Pattern\PatternOptions;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Pattern\TestAsset\TestObjectCache;

use function get_class;
use function implode;
use function ob_end_clean;
use function ob_get_contents;
use function ob_implicit_flush;
use function ob_start;

/**
 * @group      Laminas_Cache
 */
class ObjectCacheTest extends AbstractCommonPatternTest
{
    /** @var StorageInterface */
    protected $storage;

    /** @var PatternOptions */
    private $options;

    public function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0,
        ]);
        $this->options = new PatternOptions([
            'object'  => new TestObjectCache(),
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\ObjectCache();
        $this->pattern->setOptions($this->options);

        parent::setUp();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPatternNamesProvider()
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
        $usedKey      = null;
        $this->options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params  = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->pattern->call('emptyMethod', $args);
        self::assertEquals($generatedKey, $usedKey);
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
        self::assertSame(get_class($this->options->getObject()), $this->options->getObjectKey());
    }

    protected function executeMethodAndMakeAssertions(string $method, array $args): void
    {
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';
        $callback   = [$this->pattern, $method];

        // first call - not cached
        $firstCounter = TestObjectCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = $callback(...$args);
        $data   = ob_get_contents();
        ob_end_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        self::assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = $callback(...$args);
        $data   = ob_get_contents();
        ob_end_clean();

        self::assertEquals($returnSpec . $firstCounter, $return);
        if ($this->options->getCacheOutput()) {
            self::assertEquals($outputSpec . $firstCounter, $data);
        } else {
            self::assertEquals('', $data);
        }
    }
}
