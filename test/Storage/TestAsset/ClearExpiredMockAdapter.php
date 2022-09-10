<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\TestAsset;

use Laminas\Cache\Storage\ClearExpiredInterface;

class ClearExpiredMockAdapter extends MockAdapter implements ClearExpiredInterface
{
    public function clearExpired()
    {
    }
}
