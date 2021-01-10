<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Exception\LogicException;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\CaptureCache<extended>
 */
class CaptureCacheTest extends CommonPatternTest
{
    // @codingStandardsIgnoreStart
    /**
     * @var string
     */
    protected $_tmpCacheDir;

    /**
     * @var int
     */
    protected $_umask;

    /**
     * @var array<string,mixed>
     */
    protected $_bufferedServerSuperGlobal;

    /**
     * @var Cache\Pattern\PatternOptions
     */
    private $_options;
    // @codingStandardsIgnoreEnd

    public function setUp(): void
    {
        $this->_bufferedServerSuperGlobal = $_SERVER;
        $this->_umask = umask();

        $this->_tmpCacheDir = @tempnam(sys_get_temp_dir(), 'laminas_cache_test_');
        if (! $this->_tmpCacheDir) {
            $err = error_get_last();
            self::fail("Can't create temporary cache directory-file: {$err['message']}");
        } elseif (! @unlink($this->_tmpCacheDir)) {
            $err = error_get_last();
            self::fail("Can't remove temporary cache directory-file: {$err['message']}");
        } elseif (! @mkdir($this->_tmpCacheDir, 0777)) {
            $err = error_get_last();
            self::fail("Can't create temporary cache directory: {$err['message']}");
        }

        $this->_options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->_tmpCacheDir
        ]);
        $this->_pattern = new Cache\Pattern\CaptureCache();
        $this->_pattern->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown(): void
    {
        $_SERVER = $this->_bufferedServerSuperGlobal;

        $this->_removeRecursive($this->_tmpCacheDir);

        if ($this->_umask !== umask()) {
            umask($this->_umask);
            self::fail("Umask wasn't reset");
        }

        parent::tearDown();
    }

    // @codingStandardsIgnoreStart
    protected function _removeRecursive($dir)
    {
        // @codingStandardsIgnoreEnd
        if (file_exists($dir)) {
            $dirIt = new \DirectoryIterator($dir);
            foreach ($dirIt as $entry) {
                $fname = $entry->getFilename();
                if ($fname === '.' || $fname === '..') {
                    continue;
                }

                if ($entry->isFile()) {
                    unlink($entry->getPathname());
                } else {
                    $this->_removeRecursive($entry->getPathname());
                }
            }

            rmdir($dir);
        }
    }

    public function getCommonPatternNamesProvider()
    {
        return [
            ['capture'],
            ['Capture'],
        ];
    }

    public function testSetThrowsLogicExceptionOnMissingPublicDir(): void
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException(LogicException::class);
        $captureCache->set('content', '/pageId');
    }

    public function testSetWithNormalPageId(): void
    {
        $this->_pattern->set('content', '/dir1/dir2/file');
        self::assertFileExists($this->_tmpCacheDir . '/dir1/dir2/file');
        self::assertStringEqualsFile($this->_tmpCacheDir . '/dir1/dir2/file', 'content');
    }

    public function testSetWithIndexFilename(): void
    {
        $this->_options->setIndexFilename('test.html');

        $this->_pattern->set('content', '/dir1/dir2/');
        self::assertFileExists($this->_tmpCacheDir . '/dir1/dir2/test.html');
        self::assertStringEqualsFile($this->_tmpCacheDir . '/dir1/dir2/test.html', 'content');
    }

    public function testGetThrowsLogicExceptionOnMissingPublicDir(): void
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException(LogicException::class);
        $captureCache->get('/pageId');
    }

    public function testHasThrowsLogicExceptionOnMissingPublicDir(): void
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException(LogicException::class);
        $captureCache->has('/pageId');
    }

    public function testRemoveThrowsLogicExceptionOnMissingPublicDir(): void
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException(LogicException::class);
        $captureCache->remove('/pageId');
    }

    public function testGetFilenameWithoutPublicDir(): void
    {
        $captureCache = new Cache\Pattern\CaptureCache();
        self::assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/index.html'),
            $captureCache->getFilename('/')
        );
        self::assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test'),
            $captureCache->getFilename('/dir1/test')
        );
        self::assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename('/dir1/test.html')
        );
        self::assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/dir2/test.html'),
            $captureCache->getFilename('/dir1/dir2/test.html')
        );
    }

    public function testGetFilenameWithoutPublicDirAndNoPageId(): void
    {
        $_SERVER['REQUEST_URI'] = '/dir1/test.html';
        $captureCache = new Cache\Pattern\CaptureCache();
        self::assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename()
        );
    }

    public function testGetFilenameWithPublicDir(): void
    {
        $options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->_tmpCacheDir
        ]);

        $captureCache = new Cache\Pattern\CaptureCache();
        $captureCache->setOptions($options);

        self::assertEquals(
            $this->_tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/index.html'),
            $captureCache->getFilename('/')
        );
        self::assertEquals(
            $this->_tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test'),
            $captureCache->getFilename('/dir1/test')
        );
        self::assertEquals(
            $this->_tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename('/dir1/test.html')
        );
        self::assertEquals(
            $this->_tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/dir2/test.html'),
            $captureCache->getFilename('/dir1/dir2/test.html')
        );
    }

    public function testGetFilenameWithPublicDirAndNoPageId(): void
    {
        $_SERVER['REQUEST_URI'] = '/dir1/test.html';

        $options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->_tmpCacheDir
        ]);
        $captureCache = new Cache\Pattern\CaptureCache();
        $captureCache->setOptions($options);

        self::assertEquals(
            $this->_tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename()
        );
    }
}
