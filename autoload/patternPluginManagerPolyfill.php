<?php

use Laminas\Cache\PatternPluginManager;
use Laminas\ServiceManager\ServiceManager;

call_user_func(function () {
    $target = method_exists(ServiceManager::class, 'configure')
        ? PatternPluginManager\PatternPluginManagerV3Polyfill::class
        : PatternPluginManager\PatternPluginManagerV2Polyfill::class;

    class_alias($target, PatternPluginManager::class);
});
