<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Exception\MissingKeyException;

use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\OutputCache<extended>
 */
class OutputCacheTest extends AbstractCommonStoragePatternTest
{
    /**
     * Nesting level of output buffering used to restore on tearDown(): void
     *
     * @var null|int
     */
    protected $obLevel;

    public function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0,
        ]);

        $this->pattern = new Cache\Pattern\OutputCache($this->storage);

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

    public function getCommonPatternNamesProvider(): array
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
        self::assertEquals($output, $this->pattern->getStorage()->getItem($key));
    }

    public function testStartEndCacheHit(): void
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheHit';

        // fill cache
        $this->pattern->getStorage()->setItem($key, $output);

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
