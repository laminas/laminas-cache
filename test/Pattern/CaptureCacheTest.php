<?php

namespace LaminasTest\Cache\Pattern;

use DirectoryIterator;
use Laminas\Cache;
use Laminas\Cache\Exception\LogicException;

use function error_get_last;
use function file_exists;
use function mkdir;
use function rmdir;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function umask;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\CaptureCache<extended>
 */
class CaptureCacheTest extends AbstractCommonPatternTest
{
    /** @var string */
    protected $tmpCacheDir;

    /** @var int */
    protected $umask;

    /** @var array<string,mixed> */
    protected $bufferedServerSuperGlobal;

    public function setUp(): void
    {
        $this->bufferedServerSuperGlobal = $_SERVER;
        $this->umask                     = umask();

        $this->tmpCacheDir = @tempnam(sys_get_temp_dir(), 'laminas_cache_test_');
        if (! $this->tmpCacheDir) {
            $err = error_get_last();
            self::fail("Can't create temporary cache directory-file: {$err['message']}");
        } elseif (! @unlink($this->tmpCacheDir)) {
            $err = error_get_last();
            self::fail("Can't remove temporary cache directory-file: {$err['message']}");
        } elseif (! @mkdir($this->tmpCacheDir, 0777)) {
            $err = error_get_last();
            self::fail("Can't create temporary cache directory: {$err['message']}");
        }

        $this->options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->tmpCacheDir,
        ]);
        $this->pattern = new Cache\Pattern\CaptureCache();
        $this->pattern->setOptions($this->options);

        parent::setUp();
    }

    public function tearDown(): void
    {
        $_SERVER = $this->bufferedServerSuperGlobal;

        $this->removeRecursive($this->tmpCacheDir);

        if ($this->umask !== umask()) {
            umask($this->umask);
            self::fail("Umask wasn't reset");
        }

        parent::tearDown();
    }

    protected function removeRecursive(string $dir): void
    {
        if (file_exists($dir)) {
            $dirIt = new DirectoryIterator($dir);
            foreach ($dirIt as $entry) {
                $fname = $entry->getFilename();
                if ($fname === '.' || $fname === '..') {
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

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function getCommonPatternNamesProvider()
    {
        return [
            'lowercase' => ['capture'],
            'lcfirst'   => ['Capture'],
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
        $this->pattern->set('content', '/dir1/dir2/file');
        self::assertFileExists($this->tmpCacheDir . '/dir1/dir2/file');
        self::assertStringEqualsFile($this->tmpCacheDir . '/dir1/dir2/file', 'content');
    }

    public function testSetWithIndexFilename(): void
    {
        $this->options->setIndexFilename('test.html');

        $this->pattern->set('content', '/dir1/dir2/');
        self::assertFileExists($this->tmpCacheDir . '/dir1/dir2/test.html');
        self::assertStringEqualsFile($this->tmpCacheDir . '/dir1/dir2/test.html', 'content');
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
        $captureCache           = new Cache\Pattern\CaptureCache();
        self::assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename()
        );
    }

    public function testGetFilenameWithPublicDir(): void
    {
        $options = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->tmpCacheDir,
        ]);

        $captureCache = new Cache\Pattern\CaptureCache();
        $captureCache->setOptions($options);

        self::assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/index.html'),
            $captureCache->getFilename('/')
        );
        self::assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test'),
            $captureCache->getFilename('/dir1/test')
        );
        self::assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename('/dir1/test.html')
        );
        self::assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/dir2/test.html'),
            $captureCache->getFilename('/dir1/dir2/test.html')
        );
    }

    public function testGetFilenameWithPublicDirAndNoPageId(): void
    {
        $_SERVER['REQUEST_URI'] = '/dir1/test.html';

        $options      = new Cache\Pattern\PatternOptions([
            'public_dir' => $this->tmpCacheDir,
        ]);
        $captureCache = new Cache\Pattern\CaptureCache();
        $captureCache->setOptions($options);

        self::assertEquals(
            $this->tmpCacheDir . str_replace('/', DIRECTORY_SEPARATOR, '/dir1/test.html'),
            $captureCache->getFilename()
        );
    }
}
