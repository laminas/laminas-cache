<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Storage;

/**
 * @category   Laminas
 * @package    Laminas_Cache
 * @subpackage Storage
 */
interface ClearByNamespaceInterface
{
    /**
     * Remove items of given namespace
     *
     * @param string $namespace
     * @return boolean
     */
    public function clearByNamespace($namespace);
}
