<?php

namespace Laminas\Cache\Storage\Plugin;

use Laminas\EventManager\ListenerAggregateInterface;

interface PluginInterface extends ListenerAggregateInterface
{
    /**
     * Set options
     *
     * @return PluginInterface
     */
    public function setOptions(PluginOptions $options): self;

    /**
     * Get options
     */
    public function getOptions(): PluginOptions;
}
