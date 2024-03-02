<?php

namespace Laminas\Cache\Storage\Plugin;

use Laminas\EventManager\AbstractListenerAggregate;
use Webmozart\Assert\Assert;

abstract class AbstractPlugin extends AbstractListenerAggregate implements PluginInterface
{
    protected ?PluginOptions $options = null;

    /**
     * Set pattern options
     */
    public function setOptions(PluginOptions $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get all pattern options
     */
    public function getOptions(): PluginOptions
    {
        if (null === $this->options) {
            $this->setOptions(new PluginOptions());
        }

        Assert::notNull($this->options);
        return $this->options;
    }
}
