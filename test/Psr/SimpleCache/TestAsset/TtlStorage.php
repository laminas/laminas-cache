<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Psr\SimpleCache\TestAsset;

use Laminas\Cache\Storage\Adapter;
use Laminas\Cache\Storage\Capabilities;

class TtlStorage extends Adapter\AbstractAdapter
{
    /** @var array */
    private $data = [];

    /** @var array */
    public $ttl = [];

    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        $success = isset($this->data[$normalizedKey]);

        return $success ? $this->data[$normalizedKey] : null;
    }

    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $this->ttl[$normalizedKey] = $this->getOptions()->getTtl();

        $this->data[$normalizedKey] = $value;
        return true;
    }

    protected function internalRemoveItem(& $normalizedKey)
    {
        unset($this->data[$normalizedKey]);
        return true;
    }

    public function setCapabilities(Capabilities $capabilities)
    {
        $this->capabilities = $capabilities;
    }
}
