<?php

namespace Laminas\Cache\Pattern;

use Laminas\Cache\Exception;

/**
 * @deprecated This pattern will be removed in 3.0.0.
 */
class ClassCache extends CallbackCache
{

    public function setOptions(PatternOptions $options)
    {
        parent::setOptions($options);

        if (! $options->getClass()) {
            throw new Exception\InvalidArgumentException("Missing option 'class'");
        }

        if (! $this->getStorage()) {
            throw new Exception\InvalidArgumentException("Missing option 'storage'");
        }

        return $this;
    }

    /**
     * Call and cache a class method
     *
     * @param  string $method  Method name to call
     * @param  array  $args    Method arguments
     * @return mixed
     * @throws Exception\RuntimeException
     * @throws \Exception
     */
    public function call($method, array $args = [])
    {
        $options   = $this->getOptions();
        $classname = $options->getClass();
        $method    = strtolower($method);

        $cache = $options->getCacheByDefault();
        if ($cache) {
            $cache = ! in_array($method, $options->getClassNonCacheMethods());
        } else {
            $cache = in_array($method, $options->getClassCacheMethods());
        }

        if (! $cache) {
            return $classname::$method(...$args);
        }

        $callable = $classname . '::' . $method;
        return parent::call($callable, $args);
    }

    /**
     * Generate a unique key in base of a key representing the callback part
     * and a key representing the arguments part.
     *
     * @param  string     $method  The method
     * @param  array      $args    Callback arguments
     * @return string
     * @throws Exception\RuntimeException
     */
    public function generateKey($method, array $args = [])
    {
        return $this->generateCallbackKey(
            $this->getOptions()->getClass() . '::' . $method,
            $args
        );
    }

    /**
     * Generate a unique key in base of a key representing the callback part
     * and a key representing the arguments part.
     *
     * @param  callable   $callback  A valid callback
     * @param  array      $args      Callback arguments
     * @return string
     * @throws Exception\RuntimeException
     */
    protected function generateCallbackKey($callback, array $args)
    {
        $callbackKey = md5(strtolower($callback));
        $argumentKey = $this->generateArgumentsKey($args);
        return $callbackKey . $argumentKey;
    }

    /**
     * Calling a method of the entity.
     *
     * @param  string $method  Method name to call
     * @param  array  $args    Method arguments
     * @return mixed
     * @throws Exception\RuntimeException
     * @throws \Exception
     */
    public function __call($method, array $args)
    {
        return $this->call($method, $args);
    }

    /**
     * Set a static property
     *
     * @param  string $name
     * @param  mixed  $value
     * @return void
     * @see   http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __set($name, $value)
    {
        $class = $this->getOptions()->getClass();
        $class::$name = $value;
    }

    /**
     * Get a static property
     *
     * @param  string $name
     * @return mixed
     * @see    http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get($name)
    {
        $class = $this->getOptions()->getClass();
        return $class::$name;
    }

    /**
     * Is a static property exists.
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        $class = $this->getOptions()->getClass();
        return isset($class::$name);
    }

    /**
     * Unset a static property
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        $class = $this->getOptions()->getClass();
        unset($class::$name);
    }
}
