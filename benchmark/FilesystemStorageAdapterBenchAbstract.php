<?php

namespace LaminasBench\Cache;

use DirectoryIterator;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;

use function error_get_last;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function serialize;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;

/**
 * @Revs(100)
 * @Iterations(10)
 * @Warmup(1)
 */
class FilesystemStorageAdapterBenchAbstract extends AbstractCommonStorageAdapterBench
{
    /** @var string */
    private $tmpCacheDir;

    public function __construct()
    {
        $this->tmpCacheDir = (string) @tempnam(sys_get_temp_dir(), 'laminas_cache_test_');
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

        $filesystemAdapter = new class ($this->tmpCacheDir) extends AbstractAdapter {
            /** @var string */
            private $folder;

            public function __construct(string $folder)
            {
                $this->folder = $folder;

                parent::__construct();
            }

            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
             */
            protected function internalGetItem(&$normalizedKey, &$success = null, &$casToken = null)
            {
                $filename = $this->folder . DIRECTORY_SEPARATOR . $normalizedKey;
                $success  = file_exists($filename);

                return $success ? unserialize(file_get_contents($filename)) : null;
            }

            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
             */
            protected function internalSetItem(&$normalizedKey, &$value)
            {
                $filename = $this->folder . DIRECTORY_SEPARATOR . $normalizedKey;
                return (bool) file_put_contents($filename, serialize($value));
            }

            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
             */
            protected function internalRemoveItem(&$normalizedKey)
            {
                $filename = $this->folder . DIRECTORY_SEPARATOR . $normalizedKey;
                return unlink($filename);
            }
        };

        $this->storage = $filesystemAdapter;

        parent::__construct();
    }

    public function __destruct()
    {
        $this->removeRecursive($this->tmpCacheDir);
    }

    private function removeRecursive(string $dir): void
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
}
