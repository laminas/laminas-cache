<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;
use Throwable;

use function array_key_exists;
use function array_values;
use function is_callable;
use function is_object;
use function md5;
use function ob_end_flush;
use function ob_get_flush;
use function ob_implicit_flush;
use function ob_start;
use function serialize;
use function strtolower;

class CallbackCache extends AbstractStorageCapablePattern
{
    /**
     * Call the specified callback or get the result from cache
     *
     * @param  callable   $callback  A valid callback
     * @param  array      $args      Callback arguments
     * @throws Exception\RuntimeException If invalid cached data.
     * @throws Throwable
     */
    public function call(callable $callback, array $args = []): mixed
    {
        $options = $this->getOptions();
        $storage = $this->getStorage();
        $success = null;
        $key     = $this->generateCallbackKey($callback, $args);
        $result  = $storage->getItem($key, $success);
        if ($success) {
            if (! array_key_exists(0, $result)) {
                throw new Exception\RuntimeException("Invalid cached data for key '{$key}'");
            }

            echo $result[1] ?? '';
            return $result[0];
        }

        $cacheOutput = $options->getCacheOutput();
        if ($cacheOutput) {
            ob_start();
            ob_implicit_flush(false);
        }

        // TODO: do not cache on errors using [set|restore]_error_handler

        try {
            $ret = $callback(...$args);
        } catch (Throwable $throwable) {
            if ($cacheOutput) {
                ob_end_flush();
            }
            throw $throwable;
        }

        if ($cacheOutput) {
            $data = [$ret, ob_get_flush()];
        } else {
            $data = [$ret];
        }

        $storage->setItem($key, $data);

        return $ret;
    }

    /**
     * function call handler
     *
     * @param  callable-string $function  Function name to call
     * @param  array  $args      Function arguments
     * @throws Exception\RuntimeException
     * @throws Throwable
     */
    public function __call(string $function, array $args): mixed
    {
        return $this->call($function, $args);
    }

    /**
     * Generate a unique key in base of a key representing the callback part
     * and a key representing the arguments part.
     *
     * @param  callable   $callback  A valid callback
     * @param  array      $args      Callback arguments
     * @return non-empty-string
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function generateKey(callable $callback, array $args = []): string
    {
        return $this->generateCallbackKey($callback, $args);
    }

    /**
     * Generate a unique key in base of a key representing the callback part
     * and a key representing the arguments part.
     *
     * @param  callable $callback  A valid callback
     * @param  array      $args      Callback arguments
     * @return non-empty-string
     * @throws Exception\RuntimeException If callback not serializable.
     * @throws Exception\InvalidArgumentException If invalid callback.
     */
    protected function generateCallbackKey(callable $callback, array $args): string
    {
        if (! is_callable($callback, false, $callbackKey)) {
            throw new Exception\InvalidArgumentException('Invalid callback');
        }

        // functions, methods and classnames are case-insensitive
        $callbackKey = strtolower($callbackKey);

        $object = null;

        // generate a unique key of object callbacks
        if (is_object($callback)) {
            // Closures & __invoke
            $object = $callback;
        } elseif (isset($callback[0])) {
            // array($object, 'method')
            $object = $callback[0];
        }

        if (is_object($object)) {
            try {
                $serializedObject = serialize($object);
            } catch (Throwable $throwable) {
                throw new Exception\RuntimeException(
                    "Can't serialize callback: see previous error",
                    previous: $throwable,
                );
            }

            $callbackKey .= $serializedObject;
        }

        return md5($callbackKey) . $this->generateArgumentsKey($args);
    }

    /**
     * Generate a unique key of the argument part.
     *
     * @throws Exception\RuntimeException
     */
    protected function generateArgumentsKey(array $args): string
    {
        if ($args === []) {
            return '';
        }

        try {
            $serializedArgs = serialize(array_values($args));
        } catch (Throwable $throwable) {
            throw new Exception\RuntimeException(
                "Can't serialize arguments: see previous exception",
                previous: $throwable,
            );
        }

        return md5($serializedArgs);
    }
}
