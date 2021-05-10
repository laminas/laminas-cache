<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;

abstract class AbstractPattern implements PatternInterface
{
    /**
     * @var PatternOptions
     */
    protected $options;

    /**
     * Set pattern options
     *
     * @param  PatternOptions $options
     * @return AbstractPattern Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setOptions(PatternOptions $options)
    {
        if (! $options instanceof PatternOptions) {
            $options = new PatternOptions($options);
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Get all pattern options
     *
     * @return PatternOptions
     */
    public function getOptions()
    {
        if (null === $this->options) {
            $this->setOptions(new PatternOptions());
        }
        return $this->options;
    }
}
