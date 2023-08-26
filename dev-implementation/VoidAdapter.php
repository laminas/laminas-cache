<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Adapter\Dev;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;

final class VoidAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected function internalGetItem(&$normalizedKey, &$success = null, mixed &$casToken = null)
    {
        $success = false;

        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalSetItem(&$normalizedKey, mixed &$value)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalRemoveItem(&$normalizedKey)
    {
        return true;
    }
}
