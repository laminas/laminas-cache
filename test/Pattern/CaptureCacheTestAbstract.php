<?php

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use Laminas\Cache\Exception\LogicException;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Pattern\CaptureCache<extended>
 */
class CaptureCacheTestAbstract extends AbstractCommonPatternTest
{
    protected $tmpCacheDir;
    protected $umask;
    protected $bufferedServerSuperGlobal;

    protected function setUp(): void
    {
        $this->bufferedServerSuperGlobal = $_SERVER;
        $this->umask = umask();

        $this->tmpCacheDir = @tempnam(sys_get_temp_dir(), 'laminas_cache_test_');
        if (! $this->tmpCacheDir) {
            $err = error_get_last();
            $this->fail("Can't create temporary cache directory-file: {$err['message']}");
        } elseif (! @unlink($this->tmpCacheDir)) {
            $err = error_get_last();
            $this->fail("Can't remove temporary cache directory-file: {$err['message']}");
        } elseif (! @mkdir($this->tmpCacheDir, 0777)) {
            $err = error_get_last();
            $this->fail("Can't create temporary cache directory: {$err['message']}");
        }

        $this->options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->tmpCacheDir
        ]);
        $this->pattern = new Cache\Pattern\CaptureCache();
        $this->pattern->setOptions($this->options);

        parent::setUp();
    }

    public function tearDown(): void
    {
        $_SERVER = $this->bufferedServerSuperGlobal;

        $this->removeRecursive($this->tmpCacheDir);

        if ($this->umask != umask()) {
            umask($this->umask);
            $this->fail("Umask wasn't reset");
        }

        parent::tearDown();
    }

    protected function removeRecursive($dir)
    {
        if (file_exists($dir)) {
            $dirIt = new \DirectoryIterator($dir);
            foreach ($dirIt as $entry) {
                $fname = $entry->getFilename();
                if ($fname == '.' || $fname == '..') {
                    continue;
                }

                if ($entry->isFile()) {
                    unlink($entry->getPathname());
                } else {
                    $this->removeRecursive($entry->getPathname());
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

    public function testSetThrowsLogicExceptionOnMissingPublicDir()
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException('Laminas\Cache\Exception\LogicException');
        $captureCache->set('content', '/pageId');
    }

    public function testSetWithNormalPageId()
    {
        $this->pattern->set('content', '/dir1/dir2/file');
        $this->assertFileExists($this->tmpCacheDir . '/dir1/dir2/file');
        $this->assertSame(file_get_contents($this->tmpCacheDir . '/dir1/dir2/file'), 'content');
    }

    public function testSetWithIndexFilename()
    {
        $this->options->setIndexFilename('test.html');

        $this->pattern->set('content', '/dir1/dir2/');
        $this->assertFileExists($this->tmpCacheDir . '/dir1/dir2/test.html');
        $this->assertSame(file_get_contents($this->tmpCacheDir . '/dir1/dir2/test.html'), 'content');
    }

    public function testGetThrowsLogicExceptionOnMissingPublicDir()
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException(LogicException::class);
        $captureCache->get('/pageId');
    }

    public function testHasThrowsLogicExceptionOnMissingPublicDir()
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException(LogicException::class);
        $captureCache->has('/pageId');
    }

    public function testRemoveThrowsLogicExceptionOnMissingPublicDir()
    {
        $captureCache = new Cache\Pattern\CaptureCache();

        $this->expectException(LogicException::class);
        $captureCache->remove('/pageId');
    }

    public function testGetFilenameWithoutPublicDir()
    {
        $captureCache = new Cache\Pattern\CaptureCache();
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/index.html'),
            $captureCache->getFilename('/')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test'),
            $captureCache->getFilename('/dir1/test')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename('/dir1/test.html')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/dir2/test.html'),
            $captureCache->getFilename('/dir1/dir2/test.html')
        );
    }

    public function testGetFilenameWithoutPublicDirAndNoPageId()
    {
        $_SERVER['REQUEST_URI'] = '/dir1/test.html';
        $captureCache = new Cache\Pattern\CaptureCache();
        $this->assertEquals(str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'), $captureCache->getFilename());
    }

    public function testGetFilenameWithPublicDir()
    {
        $options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->tmpCacheDir
        ]);

        $captureCache = new Cache\Pattern\CaptureCache();
        $captureCache->setOptions($options);

        $this->assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/index.html'),
            $captureCache->getFilename('/')
        );
        $this->assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test'),
            $captureCache->getFilename('/dir1/test')
        );
        $this->assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename('/dir1/test.html')
        );
        $this->assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/dir2/test.html'),
            $captureCache->getFilename('/dir1/dir2/test.html')
        );
    }

    public function testGetFilenameWithPublicDirAndNoPageId()
    {
        $_SERVER['REQUEST_URI'] = '/dir1/test.html';

        $options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->tmpCacheDir
        ]);
        $captureCache = new Cache\Pattern\CaptureCache();
        $captureCache->setOptions($options);

        $this->assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename()
        );
    }
}
