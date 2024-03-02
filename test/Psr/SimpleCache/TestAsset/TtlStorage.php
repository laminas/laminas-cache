<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\SimpleCache\TestAsset;

use Laminas\Cache\Storage\Adapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Capabilities;

/**
 * @template TOptions of AdapterOptions
 * @template-extends Adapter\AbstractAdapter<TOptions>
 */
class TtlStorage extends Adapter\AbstractAdapter
{
    private array $data = [];

    /** @var array */
    public array $ttl = [];

    protected function internalGetItem(string $normalizedKey, ?bool &$success = null, mixed &$casToken = null): mixed
    {
        $success = isset($this->data[$normalizedKey]);

        return $success ? $this->data[$normalizedKey] : null;
    }

    protected function internalSetItem(string $normalizedKey, mixed $value): bool
    {
        $this->ttl[$normalizedKey] = $this->getOptions()->getTtl();

        $this->data[$normalizedKey] = $value;
        return true;
    }

    protected function internalRemoveItem(string $normalizedKey): bool
    {
        unset($this->data[$normalizedKey]);
        return true;
    }

    public function setCapabilities(Capabilities $capabilities): void
    {
        $this->capabilities = $capabilities;
    }
}
