<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Cache\Storage\Plugin;

use Laminas\Cache\Exception;
use Laminas\Cache\Storage\ExceptionEvent;
use Laminas\EventManager\EventManagerInterface;

class ExceptionHandler extends AbstractPlugin
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
     * @return ExceptionHandler
     * @throws Exception\LogicException
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $index = spl_object_hash($events);
        if (isset($this->handles[$index])) {
            throw new Exception\LogicException('Plugin already attached');
        }

        $callback = array($this, 'onException');
        $handles  = array();
        $this->handles[$index] = & $handles;

        // read
        $handles[] = $events->attach('getItem.exception', $callback, $priority);
        $handles[] = $events->attach('getItems.exception', $callback, $priority);

        $handles[] = $events->attach('hasItem.exception', $callback, $priority);
        $handles[] = $events->attach('hasItems.exception', $callback, $priority);

        $handles[] = $events->attach('getMetadata.exception', $callback, $priority);
        $handles[] = $events->attach('getMetadatas.exception', $callback, $priority);

        // write
        $handles[] = $events->attach('setItem.exception', $callback, $priority);
        $handles[] = $events->attach('setItems.exception', $callback, $priority);

        $handles[] = $events->attach('addItem.exception', $callback, $priority);
        $handles[] = $events->attach('addItems.exception', $callback, $priority);

        $handles[] = $events->attach('replaceItem.exception', $callback, $priority);
        $handles[] = $events->attach('replaceItems.exception', $callback, $priority);

        $handles[] = $events->attach('touchItem.exception', $callback, $priority);
        $handles[] = $events->attach('touchItems.exception', $callback, $priority);

        $handles[] = $events->attach('removeItem.exception', $callback, $priority);
        $handles[] = $events->attach('removeItems.exception', $callback, $priority);

        $handles[] = $events->attach('checkAndSetItem.exception', $callback, $priority);

        // increment / decrement item(s)
        $handles[] = $events->attach('incrementItem.exception', $callback, $priority);
        $handles[] = $events->attach('incrementItems.exception', $callback, $priority);

        $handles[] = $events->attach('decrementItem.exception', $callback, $priority);
        $handles[] = $events->attach('decrementItems.exception', $callback, $priority);

        return $this;
    }

    /**
     * Detach
     *
     * @param  EventManagerInterface $events
     * @return ExceptionHandler
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
     * On exception
     *
     * @param  ExceptionEvent $event
     * @return void
     */
    public function onException(ExceptionEvent $event)
    {
        $options  = $this->getOptions();
        $callback = $options->getExceptionCallback();
        if ($callback) {
            call_user_func($callback, $event->getException());
        }

        $event->setThrowException($options->getThrowExceptions());
    }
}
