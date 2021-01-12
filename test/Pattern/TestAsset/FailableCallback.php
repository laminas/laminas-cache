<?php

namespace LaminasTest\Cache\Pattern\TestAsset;

use Exception;

final class FailableCallback
{
    public function __invoke()
    {
        throw new Exception('This callback should either fail or never be invoked');
    }
}
