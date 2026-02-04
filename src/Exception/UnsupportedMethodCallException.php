<?php

namespace Laminas\Cache\Exception;

use BadMethodCallException;

/** @final */
class UnsupportedMethodCallException extends BadMethodCallException implements
    ExceptionInterface
{
}
