<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Exception\MissingKeyException;
use Laminas\Cache\Storage\StorageInterface;

use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\OutputCache<extended>
 */
class OutputCacheTest extends AbstractCommonPatternTest
{
    /** @var StorageInterface */
    protected $storage;

    /**
     * Nesting level of output buffering used to restore on tearDown()
     *
     * @var null|int
     */
    protected $obLevel;

    public function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0,
        ]);
        $this->options = new Cache\Pattern\PatternOptions([
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\OutputCache();
        $this->pattern->setOptions($this->options);

        // used to reset the level on tearDown
        $this->obLevel = ob_get_level();

        parent::setUp();
    }

    public function tearDown(): void
    {
        if ($this->obLevel > ob_get_level()) {
            for ($i = ob_get_level(); $i < $this->obLevel; $i++) {
                ob_start();
            }
            $this->fail("Nesting level of output buffering to often ended");
        } elseif ($this->obLevel < ob_get_level()) {
            for ($i = ob_get_level(); $i > $this->obLevel; $i--) {
                ob_end_clean();
            }
            $this->fail("Nesting level of output buffering not well restored");
        }

        parent::tearDown();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPatternNamesProvider()
    {
        return [
            'lowercase' => ['output'],
            'lcfirst'   => ['Output'],
        ];
    }

    public function testStartEndCacheMiss(): void
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheMiss';

        ob_start();
        self::assertFalse($this->pattern->start($key));
        echo $output;
        self::assertTrue($this->pattern->end());
        $data = ob_get_clean();

        self::assertEquals($output, $data);
        self::assertEquals($output, $this->pattern->getOptions()->getStorage()->getItem($key));
    }

    public function testStartEndCacheHit(): void
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheHit';

        // fill cache
        $this->pattern->getOptions()->getStorage()->setItem($key, $output);

        ob_start();
        self::assertTrue($this->pattern->start($key));
        $data = ob_get_clean();

        self::assertSame($output, $data);
    }

    public function testThrowMissingKeyException(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->pattern->start(''); // empty key
    }
}
