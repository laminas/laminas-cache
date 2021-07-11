<?php

namespace Laminas\Cache\Pattern;

interface PatternInterface
{
    /**
     * Set pattern options
     *
     * @param  PatternOptions $options
     * @return PatternInterface
     * @deprecated This method will be removed with v3.0. Options should be passed via instantiation.
     */
    public function setOptions(PatternOptions $options);

    /**
     * Get all pattern options
     *
     * @return PatternOptions
     */
    public function getOptions();
}
