<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Pattern\PatternOptions;

abstract class AbstractPattern implements PatternInterface
{
    public function __construct(protected ?PatternOptions $options = null)
    {
    }

    /**
     * @return AbstractPattern
     */
    public function setOptions(PatternOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
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
