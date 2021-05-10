<?php

namespace Laminas\Cache\Pattern;

interface PatternInterface
{
    /**
     * Set pattern options
     *
     * @return PatternInterface
     */
    public function setOptions(PatternOptions $options);

    /**
     * Get all pattern options
     *
     * @return PatternOptions
     */
    public function getOptions();
}
