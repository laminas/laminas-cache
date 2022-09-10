<?php

declare(strict_types=1);

namespace Laminas\Cache\Pattern;

interface PatternInterface
{
    /**
     * Get all pattern options
     *
     * @return PatternOptions
     */
    public function getOptions();
}
