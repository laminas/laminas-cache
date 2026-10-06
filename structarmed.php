<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception')
    ->layer('Storage', 'src/Storage')
    ->layer('Pattern', 'src/Pattern')
    ->layer('PsrSimpleCache', 'src/Psr/SimpleCache')
    ->layer('PsrCacheItemPool', 'src/Psr/CacheItemPool')
    ->layer('Psr', 'src/Psr', ['src/Psr/SimpleCache', 'src/Psr/CacheItemPool'])
    ->layer('Service', 'src/Service')
    ->layer('Config', [
        'src/ConfigProvider.php',
        'src/Module.php',
    ])
    ->ruleset([
        'Exception'        => [],
        'Storage'          => ['Exception'],
        'Pattern'          => ['+Storage'],
        'Psr'              => ['PsrSimpleCache', '+Storage'],
        'PsrSimpleCache'   => ['+Psr'],
        'PsrCacheItemPool' => ['+Psr'],
        'Service'          => ['Config', '+Storage'],
        'Config'           => ['+Service'],
    ]);
