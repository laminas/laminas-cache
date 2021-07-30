<?php

namespace LaminasTest\Cache\Psr\SimpleCache\TestAsset;

use Laminas\Cache\Storage\Adapter;
use Laminas\Cache\Storage\Capabilities;

class TtlStorage extends Adapter\AbstractAdapter
{
    /** @var array */
    private $data = [];

    /** @var array */
    public $ttl = [];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalGetItem(&$normalizedKey, &$success = null, &$casToken = null)
    {
        $success = isset($this->data[$normalizedKey]);

        return $success ? $this->data[$normalizedKey] : null;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalSetItem(&$normalizedKey, &$value)
    {
        $this->ttl[$normalizedKey] = $this->getOptions()->getTtl();

        $this->data[$normalizedKey] = $value;
        return true;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalRemoveItem(&$normalizedKey)
    {
        unset($this->data[$normalizedKey]);
        return true;
    }

    public function setCapabilities(Capabilities $capabilities)
    {
        $this->capabilities = $capabilities;
    }
}
