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
    /** @var array<mixed, mixed> */
    private $data = [];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalGetItem(&$normalizedKey, &$success = null, &$casToken = null)
    {
        $success = isset($this->data[$normalizedKey]);

        return $this->data[$normalizedKey] ?? null;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalSetItem(&$normalizedKey, &$value)
    {
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
}
