<?php

declare(strict_types=1);

namespace Laminas\Cache\Exception;

use BadMethodCallException;

class UnsupportedMethodCallException extends BadMethodCallException implements
    ExceptionInterface
{
}
