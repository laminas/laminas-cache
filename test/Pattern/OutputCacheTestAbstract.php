<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Exception\MissingKeyException;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Pattern\OutputCache<extended>
 */
class OutputCacheTestAbstract extends AbstractCommonStoragePatternTest
{
    /**
     * Nesting level of output buffering used to restore on tearDown(): void
     *
     * @var null|int
     */
    protected $obLevel;

    protected function setUp(): void
    {
        $this->storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0
        ]);
        $this->options = new Cache\Pattern\PatternOptions([
            'storage' => $this->storage,
        ]);
        $this->pattern = new Cache\Pattern\OutputCache($this->storage, $this->options);

        // used to reset the level on tearDown
        $this->obLevel = ob_get_level();

        parent::setUp();
    }

    public function tearDown(): void
    {
        if ($this->obLevel > ob_get_Level()) {
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

    public function getCommonPatternNamesProvider()
    {
        return [
            ['output'],
            ['Output'],
        ];
    }

    public function testStartEndCacheMiss()
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheMiss';

        ob_start();
        $this->assertFalse($this->pattern->start($key));
        echo $output;
        $this->assertTrue($this->pattern->end());
        $data = ob_get_clean();

        $this->assertEquals($output, $data);
        $this->assertEquals($output, $this->pattern->getOptions()->getStorage()->getItem($key));
    }

    public function testStartEndCacheHit()
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheHit';

        // fill cache
        $this->pattern->getStorage()->setItem($key, $output);

        ob_start();
        $this->assertTrue($this->pattern->start($key));
        $data = ob_get_clean();

        $this->assertSame($output, $data);
    }

    public function testThrowMissingKeyException()
    {
        $this->expectException(MissingKeyException::class);
        $this->pattern->start(''); // empty key
    }
}
