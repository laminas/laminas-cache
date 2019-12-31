<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Storage\Plugin;

use Laminas\Cache\Exception;
use Laminas\Cache\Storage\OptimizableInterface;
use Laminas\Cache\Storage\PostEvent;
use Laminas\EventManager\EventManagerInterface;

class OptimizeByFactor extends AbstractPlugin
{
    /**
     * Handles
     *
     * @var array
     */
    protected $handles = array();

    /**
     * Attach
     *
     * @param  EventManagerInterface $events
     * @param  int                   $priority
     * @return OptimizeByFactor
     * @throws Exception\LogicException
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $index = spl_object_hash($events);
        if (isset($this->handles[$index])) {
            throw new Exception\LogicException('Plugin already attached');
        }

        $handles = array();
        $this->handles[$index] = & $handles;

        $callback = array($this, 'optimizeByFactor');
        $handles[] = $events->attach('removeItem.post',  $callback, $priority);
        $handles[] = $events->attach('removeItems.post', $callback, $priority);

        return $this;
    }

    /**
     * Detach
     *
     * @param  EventManagerInterface $events
     * @return OptimizeByFactor
     * @throws Exception\LogicException
     */
    public function detach(EventManagerInterface $events)
    {
        $index = spl_object_hash($events);
        if (!isset($this->handles[$index])) {
            throw new Exception\LogicException('Plugin not attached');
        }

        // detach all handles of this index
        foreach ($this->handles[$index] as $handle) {
            $events->detach($handle);
        }

        // remove all detached handles
        unset($this->handles[$index]);

        return $this;
    }

    /**
     * Optimize by factor on a success _RESULT_
     *
     * @param  PostEvent $event
     * @return void
     */
    public function optimizeByFactor(PostEvent $event)
    {
        $storage = $event->getStorage();
        if (!($storage instanceof OptimizableInterface)) {
            return;
        }

        $factor = $this->getOptions()->getOptimizingFactor();
        if ($factor && mt_rand(1, $factor) == 1) {
            $storage->optimize();
        }
    }
}
