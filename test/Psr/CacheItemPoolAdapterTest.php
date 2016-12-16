<?php
/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @link      http://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Cache;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use Zend\Cache\Exception;
use Zend\Cache\Psr\CacheItemPoolAdapter;
use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Cache\Storage\StorageInterface;
use ZendTest\Cache\Psr\MockStorageTrait;

class CacheItemPoolAdapterTest extends TestCase
{
    use MockStorageTrait;

    /**
     * @expectedException \Zend\Cache\Psr\CacheException
     */
    public function testStorageNotFlushableThrowsException()
    {
        $storage = $this->prophesize(StorageInterface::class);
        $this->getAdapter($storage);
    }

    /**
     * @expectedException \Zend\Cache\Psr\CacheException
     */
    public function testStorageFalseStaticTtlThrowsException()
    {
        $storage = $this->getStorageProphesy(['staticTtl' => false]);
        $this->getAdapter($storage);
    }

    /**
     * @expectedException \Zend\Cache\Psr\CacheException
     */
    public function testStorageZeroMinTtlThrowsException()
    {
        $storage = $this->getStorageProphesy(['staticTtl' => true, 'minTtl' => 0]);
        $this->getAdapter($storage);
    }

    public function testUnserialize()
    {
        // we can't test this without reflection: we can't prophesy args-by-ref (ie $storage->getItem('key', $isHit))
        $unserialize = new \ReflectionMethod(CacheItemPoolAdapter::class, 'unserialize');
        $unserialize->setAccessible(true);

        $capabilities = $this->defaultCapabilities;
        $capabilities['supportedDatatypes']['object'] = false;
        $storage = $this->getStorageProphesy($capabilities);
        $adapter = $this->getAdapter($storage);

        $value = false;
        $result = $unserialize->invoke($adapter, serialize($value));
        $this->assertTrue($result['isHit'], "False value should be a hit");
        $this->assertFalse($result['value'], "False value should be unserialized correctly");

        $value = ['a' => 'b'];
        $result = $unserialize->invoke($adapter, serialize($value));
        $this->assertTrue($result['isHit'], "Array should be a hit");
        $this->assertEquals($value, $result['value'], "Array should be unserialized correctly");

        $result = $unserialize->invoke($adapter, null);
        $this->assertFalse($result['isHit'], "Unserializable value should not be a hit");
        $this->assertNull($result['value'], "Unserializable value should be null");
    }

    public function testUnsupportedDatatypeSerializesValues()
    {
        $test = ['a' => 'b'];
        foreach ($this->defaultCapabilities['supportedDatatypes'] as $type => $value) {
            if ($value) {
                $capabilities = $this->defaultCapabilities;
                $capabilities['supportedDatatypes'][$type] = false;
                $storage = $this->getStorageProphesy($capabilities, false, AbstractAdapter::class);
                $adapter = $this->getAdapter($storage);
                $item = $adapter->getItem('foo');
                $item->set($test);
                $adapter->save($item);
                $items = $adapter->getItems(['foo']);
                $this->assertEquals($test, $items['foo']->get());
            }
        }
    }

    public function testGetDeferredItem()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->saveDeferred($item);
        $item = $adapter->getItem('foo');
        $this->assertTrue($item->isHit());
        $this->assertEquals('bar', $item->get());
    }

    /**
     * @dataProvider invalidKeyProvider
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testGetItemInvalidKeyThrowsException($key)
    {
        $this->getAdapter()->getItem($key);
    }

    public function testGetItemRuntimeExceptionIsMiss()
    {
        $storage = $this->getStorageProphesy();
        $storage->getItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $this->assertFalse($item->isHit());
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testGetItemInvalidArgumentExceptionRethrown()
    {
        $storage = $this->getStorageProphesy();
        $storage->getItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->getItem('foo');
    }

    public function testGetNonexistentItems()
    {
        $keys = ['foo', 'bar'];
        $adapter = $this->getAdapter();
        $items = $adapter->getItems($keys);
        $this->assertEquals($keys, array_keys($items));
        foreach ($keys as $key) {
            $this->assertEquals($key, $items[$key]->getKey());
        }
        foreach ($items as $item) {
            $this->assertNull($item->get());
            $this->assertFalse($item->isHit());
        }
    }

    public function testGetDeferredItems()
    {
        $keys = ['foo', 'bar'];
        $adapter = $this->getAdapter();
        $items = $adapter->getItems($keys);
        foreach ($items as $item) {
            $item->set('baz');
            $adapter->saveDeferred($item);
        }
        $items = $adapter->getItems($keys);
        foreach ($items as $item) {
            $this->assertTrue($item->isHit());
        }
    }

    public function testGetMixedItems()
    {
        $keys = ['foo', 'bar'];
        $storage = $this->getStorageProphesy();
        $storage->getItems($keys)
            ->willReturn(['bar' => 'value']);
        $items = $this->getAdapter($storage)->getItems($keys);
        $this->assertEquals(2, count($items));
        $this->assertNull($items['foo']->get());
        $this->assertFalse($items['foo']->isHit());
        $this->assertEquals('value', $items['bar']->get());
        $this->assertTrue($items['bar']->isHit());
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testGetItemsInvalidKeyThrowsException()
    {
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter()->getItems($keys);
    }

    public function testGetItemsRuntimeExceptionIsMiss()
    {
        $keys = ['foo', 'bar'];
        $storage = $this->getStorageProphesy();
        $storage->getItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $items = $this->getAdapter($storage)->getItems($keys);
        $this->assertEquals(2, count($items));
        foreach ($keys as $key) {
            $this->assertFalse($items[$key]->isHit());
        }
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testGetItemsInvalidArgumentExceptionRethrown()
    {
        $storage = $this->getStorageProphesy();
        $storage->getItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->getItems(['foo', 'bar']);
    }

    public function testSaveItem()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        $this->assertEquals('bar', $saved['foo']->get());
        $this->assertTrue($saved['foo']->isHit());
    }

    public function testSaveItemWithExpiration()
    {
        $storage = $this->getStorageProphesy()->reveal();
        $adapter = new CacheItemPoolAdapter($storage);
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(3600);
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItems(['foo']);
        $this->assertEquals('bar', $saved['foo']->get());
        $this->assertTrue($saved['foo']->isHit());
        // ensure original TTL not modified
        $options = $storage->getOptions();
        $this->assertEquals(0, $options->getTtl());
    }

    public function testExpiredItemNotSaved()
    {
        $storage = $this->getStorageProphesy()->reveal();
        $adapter = new CacheItemPoolAdapter($storage);
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        $this->assertTrue($adapter->save($item));
        $saved = $adapter->getItem('foo');
        $this->assertFalse($saved->isHit());
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testSaveForeignItemThrowsException()
    {
        $item = $this->prophesize(CacheItemInterface::class);
        $this->getAdapter()->save($item->reveal());
    }

    public function testSaveItemRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphesy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $this->assertFalse($adapter->save($item));
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testSaveItemInvalidArgumentExceptionRethrown()
    {
        $storage = $this->getStorageProphesy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\InvalidArgumentException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->save($item);
    }

    public function testHasItemReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        $this->assertTrue($adapter->hasItem('foo'));
    }

    public function testHasNonexistentItemReturnsFalse()
    {
        $this->assertFalse($this->getAdapter()->hasItem('foo'));
    }

    public function testHasDeferredItemReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertTrue($adapter->hasItem('foo'));
    }

    public function testHasExpiredDeferredItemReturnsFalse()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(0);
        $adapter->saveDeferred($item);
        $this->assertFalse($adapter->hasItem('foo'));
    }

    /**
     * @dataProvider invalidKeyProvider
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testHasItemInvalidKeyThrowsException($key)
    {
        $this->getAdapter()->hasItem($key);
    }

    public function testHasItemRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphesy();
        $storage->hasItem(Argument::type('string'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->hasItem('foo'));
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testHasItemInvalidArgumentExceptionRethrown()
    {
        $storage = $this->getStorageProphesy();
        $storage->hasItem(Argument::type('string'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->hasItem('foo');
    }

    public function testClearReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
        $this->assertTrue($adapter->clear());
    }

    public function testClearEmptyReturnsTrue()
    {
        $this->assertTrue($this->getAdapter()->clear());
    }

    public function testClearDeferred()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->clear();
        $this->assertFalse($adapter->hasItem('foo'));
    }

    public function testClearRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphesy();
        $storage->flush()
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceReturnsTrue()
    {
        $storage = $this->getStorageProphesy(false, ['namespace' => 'zfcache']);
        $storage->clearByNamespace(Argument::any())->willReturn(true)->shouldBeCalled();
        $this->assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByEmptyNamespaceCallsFlush()
    {
        $storage = $this->getStorageProphesy(false, ['namespace' => '']);
        $storage->flush()->willReturn(true)->shouldBeCalled();
        $this->assertTrue($this->getAdapter($storage)->clear());
    }

    public function testClearByNamespaceRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphesy(false, ['namespace' => 'zfcache']);
        $storage->clearByNamespace(Argument::any())
            ->willThrow(Exception\RuntimeException::class)
            ->shouldBeCalled();
        $this->assertFalse($this->getAdapter($storage)->clear());
    }

    public function testDeleteItemReturnsTrue()
    {
        $this->assertTrue($this->getAdapter()->deleteItem('foo'));
    }

    public function testDeleteDeferredItem()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $adapter->deleteItem('foo');
        $this->assertFalse($adapter->hasItem('foo'));
    }

    /**
     * @dataProvider invalidKeyProvider
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testDeleteItemInvalidKeyThrowsException($key)
    {
        $this->getAdapter()->deleteItem($key);
    }

    public function testDeleteItemRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->deleteItem('foo'));
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testDeleteItemInvalidArgumentExceptionRethrown()
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->deleteItem('foo');
    }

    public function testDeleteItemsReturnsTrue()
    {
        $this->assertTrue($this->getAdapter()->deleteItems(['foo', 'bar', 'baz']));
    }

    public function testDeleteDeferredItems()
    {
        $keys = ['foo', 'bar', 'baz'];
        $adapter = $this->getAdapter();
        foreach ($keys as $key) {
            $item = $adapter->getItem($key);
            $adapter->saveDeferred($item);
        }
        $keys = ['foo', 'bar'];
        $adapter->deleteItems($keys);
        foreach ($keys as $key) {
            $this->assertFalse($adapter->hasItem($key));
        }
        $this->assertTrue($adapter->hasItem('baz'));
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testDeleteItemsInvalidKeyThrowsException()
    {
        $keys = ['ok'] + $this->getInvalidKeys();
        $this->getAdapter()->deleteItems($keys);
    }

    public function testDeleteItemsRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\RuntimeException::class);
        $this->assertFalse($this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']));
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testDeleteItemsInvalidArgumentExceptionRethrown()
    {
        $storage = $this->getStorageProphesy();
        $storage->removeItems(Argument::type('array'))
            ->willThrow(Exception\InvalidArgumentException::class);
        $this->getAdapter($storage)->deleteItems(['foo', 'bar', 'baz']);
    }

    public function testSaveDeferredReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $this->assertTrue($adapter->saveDeferred($item));
    }

    /**
     * @expectedException \Zend\Cache\Psr\InvalidArgumentException
     */
    public function testSaveDeferredForeignItemThrowsException()
    {
        $item = $this->prophesize(CacheItemInterface::class);
        $this->getAdapter()->saveDeferred($item->reveal());
    }

    public function testCommitReturnsTrue()
    {
        $adapter = $this->getAdapter();
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertTrue($adapter->commit());
    }

    public function testCommitEmptyReturnsTrue()
    {
        $this->assertTrue($this->getAdapter()->commit());
    }

    public function testCommitRuntimeExceptionReturnsFalse()
    {
        $storage = $this->getStorageProphesy();
        $storage->setItem(Argument::type('string'), Argument::any())
            ->willThrow(Exception\RuntimeException::class);
        $adapter = $this->getAdapter($storage);
        $item = $adapter->getItem('foo');
        $adapter->saveDeferred($item);
        $this->assertFalse($adapter->commit());
    }

    public function invalidKeyProvider()
    {
        return array_map(function ($v) {
            return [$v];
        }, $this->getInvalidKeys());
    }

    private function getInvalidKeys()
    {
        return [
            'key{',
            'key}',
            'key(',
            'key)',
            'key/',
            'key\\',
            'key@',
            'key:',
            new \stdClass()
        ];
    }

    /**
     * @param Prophesy $storage
     * @return CacheItemPoolAdapter
     */
    private function getAdapter($storage = null)
    {
        if (! $storage) {
            $storage = $this->getStorageProphesy();
        }
        return new CacheItemPoolAdapter($storage->reveal());
    }
}
