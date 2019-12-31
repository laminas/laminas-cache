<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\TestAsset;

use Laminas\Cache\Storage\Plugin\AbstractPlugin;

class DummyStoragePlugin extends AbstractPlugin
{

    /**
     * Overwrite constructor: do not check internal storage
     */
    public function __construct($options = array())
    {
        $this->setOptions($options);
    }
}
