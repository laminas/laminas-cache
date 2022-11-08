<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;

class MockAdapter extends AbstractAdapter
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalGetItem(&$normalizedKey, &$success = null, mixed &$casToken = null)
    {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalSetItem(&$normalizedKey, mixed &$value)
    {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function internalRemoveItem(&$normalizedKey)
    {
    }
}
