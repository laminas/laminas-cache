<?php
/**
 * @see       https://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-cache/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Cache\Psr;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use ReflectionProperty;
use Zend\Cache\Exception;
use Zend\Cache\Psr\SimpleCacheDecorator;
use Zend\Cache\Psr\SimpleCacheInvalidArgumentException;
use Zend\Cache\Psr\SimpleCacheException;
use Zend\Cache\Storage\Adapter\AdapterOptions;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\StorageInterface;

/**
 * Test the PSR-16 decorator.
 *
 * Note to maintainers: the try/catch blocks are done on purpose within this
 * class, instead of expectException*(). This is due to the fact that the
 * decorator is expected to re-throw any caught exceptions as PSR-16 exception
 * types. The class passes the original exception as the previous exception
 * when doing so, and the only way to test that this has happened is to use
 * try/catch blocks and assert identity against the result of getPrevious().
 */
class SimpleCacheDecoratorTest extends TestCase
{
    public function setUp()
    {
        $this->options = $this->prophesize(AdapterOptions::class);
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->cache = new SimpleCacheDecorator($this->storage->reveal());
    }

    public function setSuccessReference(SimpleCacheDecorator $cache, $success)
    {
        $r = new ReflectionProperty($cache, 'success');
        $r->setAccessible(true);
        $r->setValue($cache, $success);
    }

    public function testItIsASimpleCacheImplementation()
    {
        $this->assertInstanceOf(SimpleCacheInterface::class, $this->cache);
    }

    public function testGetReturnsDefaultValueWhenUnderlyingStorageDoesNotContainItem()
    {
        $testCase = $this;
        $cache = $this->cache;
        $this->storage
            ->getItem('key', Argument::any())
            ->will(function () use ($testCase, $cache) {
                // Indicating lookup succeeded, but...
                $testCase->setSuccessReference($cache, true);
                // null === not found
                return null;
            });

        $this->assertSame('default', $this->cache->get('key', 'default'));
    }

    public function testGetReturnsDefaultValueWhenStorageIndicatesFailure()
    {
        $testCase = $this;
        $cache = $this->cache;
        $this->storage
            ->getItem('key', Argument::any())
            ->will(function () use ($testCase, $cache) {
                // Indicating failure to lookup
                $testCase->setSuccessReference($cache, false);
                return false;
            });

        $this->assertSame('default', $this->cache->get('key', 'default'));
    }

    public function testGetReturnsValueReturnedByStorage()
    {
        $testCase = $this;
        $cache = $this->cache;
        $expected = 'returned value';

        $this->storage
            ->getItem('key', Argument::any())
            ->will(function () use ($testCase, $cache, $expected) {
                // Indicating lookup success
                $testCase->setSuccessReference($cache, true);
                return $expected;
            });

        $this->assertSame($expected, $this->cache->get('key', 'default'));
    }

    public function testGetShouldReRaiseExceptionThrownByStorage()
    {
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage
            ->getItem('key', Argument::any())
            ->willThrow($exception);

        try {
            $this->cache->get('key', 'default');
            $this->fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            $this->assertSame($exception->getMessage(), $e->getMessage());
            $this->assertSame($exception->getCode(), $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testSetProxiesToStorageAndModifiesAndResetsOptions()
    {
        $originalTtl = 600;
        $ttl = 86400;

        $this->options
            ->getTtl()
            ->will(function () use ($ttl, $originalTtl) {
                $this
                    ->setTtl($ttl)
                    ->will(function () use ($originalTtl) {
                        $this->setTtl($originalTtl)->shouldBeCalled();
                    });
                return $originalTtl;
            });

        $this->storage->getOptions()->will([$this->options, 'reveal']);
        $this->storage->setItem('key', 'value')->willReturn(true);

        $this->assertTrue($this->cache->set('key', 'value', $ttl));
    }

    public function testSetShouldReRaiseExceptionThrownByStorage()
    {
        $originalTtl = 600;
        $ttl = 86400;

        $this->options
            ->getTtl()
            ->will(function () use ($ttl, $originalTtl) {
                $this
                    ->setTtl($ttl)
                    ->will(function () use ($originalTtl) {
                        $this->setTtl($originalTtl)->shouldNotBeCalled();
                    });
                return $originalTtl;
            });

        $this->storage->getOptions()->will([$this->options, 'reveal']);

        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage->setItem('key', 'value')->willThrow($exception);

        try {
            $this->cache->set('key', 'value', $ttl);
            $this->fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            $this->assertSame($exception->getMessage(), $e->getMessage());
            $this->assertSame($exception->getCode(), $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testDeleteShouldProxyToStorage()
    {
        $this->storage->removeItem('key')->willReturn(true);
        $this->assertTrue($this->cache->delete('key'));
    }

    public function testDeleteShouldReRaiseExceptionThrownByStorage()
    {
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage->removeItem('key')->willThrow($exception);

        try {
            $this->cache->delete('key');
            $this->fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            $this->assertSame($exception->getMessage(), $e->getMessage());
            $this->assertSame($exception->getCode(), $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testClearReturnsFalseIfStorageIsNotFlushable()
    {
        $this->assertFalse($this->cache->clear());
    }

    public function testClearProxiesToStorageIfStorageIsFlushable()
    {
        $storage = $this->prophesize(StorageInterface::class);
        $storage->willImplement(FlushableInterface::class);
        $storage->flush()->willReturn(true);

        $cache = new SimpleCacheDecorator($storage->reveal());
        $this->assertTrue($cache->clear());
    }

    public function testGetMultipleProxiesToStorageAndProvidesDefaultsForUnfoundKeysWhenNonNullDefaultPresent()
    {
        $keys = ['one', 'two', 'three'];
        $expected = [
            'one' => 1,
            'two' => 'default',
            'three' => 3,
        ];

        $this->storage
            ->getItems($keys)
            ->willReturn([
                'one' => 1,
                'three' => 3,
            ]);

        $this->assertEquals($expected, $this->cache->getMultiple($keys, 'default'));
    }

    public function testGetMultipleProxiesToStorageAndOmitsValuesForUnfoundKeysWhenNullDefaultPresent()
    {
        $keys = ['one', 'two', 'three'];
        $expected = [
            'one' => 1,
            'three' => 3,
        ];

        $this->storage
            ->getItems($keys)
            ->willReturn([
                'one' => 1,
                'three' => 3,
            ]);

        $this->assertEquals($expected, $this->cache->getMultiple($keys));
    }

    public function testGetMultipleReRaisesExceptionFromStorage()
    {
        $keys = ['one', 'two', 'three'];
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);

        $this->storage
            ->getItems($keys)
            ->willThrow($exception);

        try {
            $this->cache->getMultiple($keys);
            $this->fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            $this->assertSame($exception->getMessage(), $e->getMessage());
            $this->assertSame($exception->getCode(), $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testSetMultipleProxiesToStorageAndModifiesAndResetsOptions()
    {
        $originalTtl = 600;
        $ttl = 86400;

        $this->options
            ->getTtl()
            ->will(function () use ($ttl, $originalTtl) {
                $this
                    ->setTtl($ttl)
                    ->will(function () use ($originalTtl) {
                        $this->setTtl($originalTtl)->shouldBeCalled();
                    });
                return $originalTtl;
            });

        $this->storage->getOptions()->will([$this->options, 'reveal']);

        $values = ['one' => 1, 'three' => 3];

        $this->storage->setItems($values)->willReturn(true);

        $this->assertTrue($this->cache->setMultiple($values, $ttl));
    }

    public function testSetMultipleReRaisesExceptionFromStorage()
    {
        $originalTtl = 600;
        $ttl = 86400;

        $this->options
            ->getTtl()
            ->will(function () use ($ttl, $originalTtl) {
                $this
                    ->setTtl($ttl)
                    ->will(function () use ($originalTtl) {
                        $this->setTtl($originalTtl)->shouldNotBeCalled();
                    });
                return $originalTtl;
            });

        $this->storage->getOptions()->will([$this->options, 'reveal']);

        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $values = ['one' => 1, 'three' => 3];

        $this->storage->setItems($values)->willThrow($exception);

        try {
            $this->cache->setMultiple($values, $ttl);
            $this->fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            $this->assertSame($exception->getMessage(), $e->getMessage());
            $this->assertSame($exception->getCode(), $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testDeleteMultipleProxiesToStorageAndReturnsTrueWhenStorageReturnsEmptyArray()
    {
        $keys = ['one', 'two', 'three'];
        $this->storage->removeItems($keys)->willReturn([]);
        $this->assertTrue($this->cache->deleteMultiple($keys));
    }

    public function testDeleteMultipleProxiesToStorageAndReturnsFalseIfStorageReturnsNonEmptyArray()
    {
        $keys = ['one', 'two', 'three'];
        $this->storage->removeItems($keys)->willReturn(['two']);
        $this->assertFalse($this->cache->deleteMultiple($keys));
    }

    public function testDeleteMultipleReRaisesExceptionThrownByStorage()
    {
        $keys = [1, 2, 3];
        $exception = new Exception\InvalidArgumentException('bad key', 500);
        $this->storage->removeItems($keys)->willThrow($exception);

        try {
            $this->cache->deleteMultiple($keys);
            $this->fail('Exception should have been raised');
        } catch (SimpleCacheInvalidArgumentException $e) {
            $this->assertSame($exception->getMessage(), $e->getMessage());
            $this->assertSame($exception->getCode(), $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function hasResultProvider()
    {
        return [
            'true' => [true],
            'false' => [false],
        ];
    }

    /**
     * @dataProvider hasResultProvider
     */
    public function testHasProxiesToStorage($result)
    {
        $this->storage->hasItem('key')->willReturn($result);
        $this->assertSame($result, $this->cache->has('key'));
    }

    public function testHasReRaisesExceptionThrownByStorage()
    {
        $exception = new Exception\ExtensionNotLoadedException('failure', 500);
        $this->storage->hasItem('key')->willThrow($exception);

        try {
            $this->cache->has('key');
            $this->fail('Exception should have been raised');
        } catch (SimpleCacheException $e) {
            $this->assertSame($exception->getMessage(), $e->getMessage());
            $this->assertSame($exception->getCode(), $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
    }
}
