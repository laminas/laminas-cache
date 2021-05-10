<?php

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
