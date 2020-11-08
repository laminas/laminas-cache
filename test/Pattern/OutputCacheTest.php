<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Pattern\OutputCache<extended>
 */
class OutputCacheTest extends CommonPatternTest
{
    // @codingStandardsIgnoreStart
    /**
     * @var \Laminas\Cache\Storage\StorageInterface
     */
    protected $_storage;

    /**
     * Nesting level of output buffering used to restore on tearDown()
     *
     * @var null|int
     */
    protected $_obLevel;
    // @codingStandardsIgnoreEnd

    public function setUp(): void
    {
        $this->_storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0
        ]);
        $this->_options = new Cache\Pattern\PatternOptions([
            'storage' => $this->_storage,
        ]);
        $this->_pattern = new Cache\Pattern\OutputCache();
        $this->_pattern->setOptions($this->_options);

        // used to reset the level on tearDown
        $this->_obLevel = ob_get_level();

        parent::setUp();
    }

    public function tearDown(): void
    {
        if ($this->_obLevel > ob_get_Level()) {
            for ($i = ob_get_level(); $i < $this->_obLevel; $i++) {
                ob_start();
            }
            $this->fail("Nesting level of output buffering to often ended");
        } elseif ($this->_obLevel < ob_get_level()) {
            for ($i = ob_get_level(); $i > $this->_obLevel; $i--) {
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

    public function testStartEndCacheMiss(): void
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheMiss';

        ob_start();
        $this->assertFalse($this->_pattern->start($key));
        echo $output;
        $this->assertTrue($this->_pattern->end());
        $data = ob_get_clean();

        $this->assertEquals($output, $data);
        $this->assertEquals($output, $this->_pattern->getOptions()->getStorage()->getItem($key));
    }

    public function testStartEndCacheHit(): void
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheHit';

        // fill cache
        $this->_pattern->getOptions()->getStorage()->setItem($key, $output);

        ob_start();
        $this->assertTrue($this->_pattern->start($key));
        $data = ob_get_clean();

        $this->assertSame($output, $data);
    }

    public function testThrowMissingKeyException(): void
    {
        $this->expectException('Laminas\Cache\Exception\MissingKeyException');
        $this->_pattern->start(''); // empty key
    }
}
