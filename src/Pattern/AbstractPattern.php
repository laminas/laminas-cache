<?php

namespace Laminas\Cache\Pattern;

abstract class AbstractPattern implements PatternInterface
{
    public function __construct(protected ?PatternOptions $options = null)
    {
    }

    public function setOptions(PatternOptions $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): PatternOptions
    {
        if (null === $this->options) {
            $this->setOptions(new PatternOptions());
        }
        return $this->options;
    }
}
