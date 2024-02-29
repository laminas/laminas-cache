<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache\Pattern\OutputCache;
use Laminas\Cache\Storage\StorageInterface;

use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;

/**
 * @group      Laminas_Cache
 * @covers \Laminas\Cache\Pattern\OutputCache<extended>
 * @template-extends AbstractCommonStoragePatternTest<OutputCache>
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
        $this->storage = $this->createMock(StorageInterface::class);

        $this->pattern = new OutputCache($this->storage);

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

        $this->storage
            ->expects(self::once())
            ->method('setItem')
            ->with($key, $output)
            ->willReturn(true);

        ob_start();
        self::assertFalse($this->pattern->start($key));
        echo $output;
        self::assertTrue($this->pattern->end());
        $data = ob_get_clean();

        self::assertEquals($output, $data);
    }

    public function testStartEndCacheHit(): void
    {
        $output = 'foobar';
        $key    = 'testStartEndCacheHit';

        $this->storage
            ->expects(self::never())
            ->method('setItem');

        $this->storage
            ->expects(self::once())
            ->method('getItem')
            ->with($key, null)
            ->willReturnCallback(function (string $key, ?bool &$success = null) use ($output): string {
                 $success = true;
                 return $output;
            });

        ob_start();
        self::assertTrue($this->pattern->start($key));
        $data = ob_get_clean();

        self::assertSame($output, $data);
    }
}
