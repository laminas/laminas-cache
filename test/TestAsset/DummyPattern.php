<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\TestAsset;

use Laminas\Cache;

class DummyPattern extends Cache\Pattern\AbstractPattern
{

    public $_dummyOption = 'dummyOption';

    public function setDummyOption($value)
    {
        $this->_dummyOption = $value;
        return $this;
    }

    public function getDummyOption()
    {
        return $this->_dummyOption;
    }
}
