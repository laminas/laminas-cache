<?php

namespace Zend\Cache\Storage\Adapter;

function unlink($path, $context = null)
{
    usleep(50000);
    if ($context) {
        return \unlink($path, $context);
    }

    return \unlink($path);
}
