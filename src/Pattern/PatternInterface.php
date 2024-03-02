<?php

namespace Laminas\Cache\Pattern;

interface PatternInterface
{
    /**
     * Get all pattern options
     */
    public function getOptions(): PatternOptions;
}
