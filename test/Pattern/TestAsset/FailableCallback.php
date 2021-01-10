<?php

namespace LaminasTest\Cache\Pattern\TestAsset;

final class FailableCallback
{
    public function __invoke()
    {
        throw new \Exception('This callback should either fail or never be invoked');
    }
}
