<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;

use function array_pop;
use function ob_get_flush;
use function ob_implicit_flush;
use function ob_start;

class OutputCache extends AbstractStorageCapablePattern
{
    /**
     * The key stack
     *
     * @var array
     */
    protected $keyStack = [];

    /**
     * @return OutputCache
     */
    public function setOptions(PatternOptions $options)
    {
        parent::setOptions($options);

        if (! $this->getStorage()) {
            throw new Exception\InvalidArgumentException("Missing option 'storage'");
        }

        return $this;
    }

    /**
     * if there is a cached item with the given key display it's data and return true
     * else start buffering output until end() is called or the script ends.
     *
     * @param  string  $key Key
     * @throws Exception\MissingKeyException If key is missing.
     * @return bool
     */
    public function start($key)
    {
        if (($key = (string) $key) === '') {
            throw new Exception\MissingKeyException('Missing key to read/write output from cache');
        }

        $success = null;
        $storage = $this->getStorage();
        $data    = $storage->getItem($key, $success);
        if ($success) {
            echo $data;
            return true;
        }

        ob_start();
        ob_implicit_flush(0);
        $this->keyStack[] = $key;
        return false;
    }

    /**
     * Stops buffering output, write buffered data to cache using the given key on start()
     * and displays the buffer.
     *
     * @throws Exception\RuntimeException If output cache not started or buffering not active.
     * @return bool TRUE on success, FALSE on failure writing to cache
     */
    public function end()
    {
        $key = array_pop($this->keyStack);
        if ($key === null) {
            throw new Exception\RuntimeException('Output cache not started');
        }

        $output = ob_get_flush();
        if ($output === false) {
            throw new Exception\RuntimeException('Output buffering not active');
        }

        $storage = $this->getStorage();
        return $storage->setItem($key, $output);
    }
}
