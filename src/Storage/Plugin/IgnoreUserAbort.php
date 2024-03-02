<?php

namespace Laminas\Cache\Storage\Plugin;

use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventManagerInterface;

use function connection_aborted;
use function ignore_user_abort;

class IgnoreUserAbort extends AbstractPlugin
{
    /**
     * The storage who activated ignore_user_abort.
     */
    protected ?StorageInterface $activatedTarget = null;

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $cbOnBefore = [$this, 'onBefore'];
        $cbOnAfter  = [$this, 'onAfter'];

        $this->listeners[] = $events->attach('setItem.pre', $cbOnBefore, $priority);
        $this->listeners[] = $events->attach('setItem.post', $cbOnAfter, $priority);
        $this->listeners[] = $events->attach('setItem.exception', $cbOnAfter, $priority);

        $this->listeners[] = $events->attach('setItems.pre', $cbOnBefore, $priority);
        $this->listeners[] = $events->attach('setItems.post', $cbOnAfter, $priority);
        $this->listeners[] = $events->attach('setItems.exception', $cbOnAfter, $priority);

        $this->listeners[] = $events->attach('addItem.pre', $cbOnBefore, $priority);
        $this->listeners[] = $events->attach('addItem.post', $cbOnAfter, $priority);
        $this->listeners[] = $events->attach('addItem.exception', $cbOnAfter, $priority);

        $this->listeners[] = $events->attach('addItems.pre', $cbOnBefore, $priority);
        $this->listeners[] = $events->attach('addItems.post', $cbOnAfter, $priority);
        $this->listeners[] = $events->attach('addItems.exception', $cbOnAfter, $priority);

        $this->listeners[] = $events->attach('replaceItem.pre', $cbOnBefore, $priority);
        $this->listeners[] = $events->attach('replaceItem.post', $cbOnAfter, $priority);
        $this->listeners[] = $events->attach('replaceItem.exception', $cbOnAfter, $priority);

        $this->listeners[] = $events->attach('replaceItems.pre', $cbOnBefore, $priority);
        $this->listeners[] = $events->attach('replaceItems.post', $cbOnAfter, $priority);
        $this->listeners[] = $events->attach('replaceItems.exception', $cbOnAfter, $priority);

        $this->listeners[] = $events->attach('checkAndSetItem.pre', $cbOnBefore, $priority);
        $this->listeners[] = $events->attach('checkAndSetItem.post', $cbOnAfter, $priority);
        $this->listeners[] = $events->attach('checkAndSetItem.exception', $cbOnAfter, $priority);
    }

    /**
     * Activate ignore_user_abort if not already done
     * and save the target who activated it.
     */
    public function onBefore(Event $event): void
    {
        if ($this->activatedTarget === null && ! ignore_user_abort(true)) {
            $this->activatedTarget = $event->getStorage();
        }
    }

    /**
     * Reset ignore_user_abort if it's activated and if it's the same target
     * who activated it.
     *
     * If exit_on_abort is enabled and the connection has been aborted
     * exit the script.
     */
    public function onAfter(Event $event): void
    {
        if ($this->activatedTarget === $event->getStorage()) {
            // exit if connection aborted
            if ($this->getOptions()->getExitOnAbort() && connection_aborted()) {
                exit;
            }

            // reset ignore_user_abort
            ignore_user_abort(false);

            // remove activated target
            $this->activatedTarget = null;
        }
    }
}
