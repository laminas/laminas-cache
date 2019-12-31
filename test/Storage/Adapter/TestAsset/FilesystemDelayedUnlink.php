<?php

namespace Laminas\Cache\Storage\Adapter;

function unlink($path, $context = null)
{
    global $unlinkDelay;
    if (isset($unlinkDelay) && $unlinkDelay > 0) {
        usleep($unlinkDelay);
    }

    return $context ? \unlink($path, $context) : \unlink($path);
}
