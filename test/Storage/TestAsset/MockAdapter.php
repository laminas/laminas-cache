<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;

class MockAdapter extends AbstractAdapter
{

    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
    }

    protected function internalSetItem(& $normalizedKey, & $value)
    {
    }

    protected function internalRemoveItem(& $normalizedKey)
    {
    }
}
