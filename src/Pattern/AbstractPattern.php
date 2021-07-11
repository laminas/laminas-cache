<?php

namespace Laminas\Cache\Pattern;

abstract class AbstractPattern implements PatternInterface
{
    /**
     * @var PatternOptions|null
     */
    protected $options;

    public function __construct(?PatternOptions $options = null)
    {
        $this->options = $options;
    }

    public function setOptions(PatternOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        if (null === $this->options) {
            $this->setOptions(new PatternOptions());
        }
        return $this->options;
    }
}
