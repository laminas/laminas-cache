<?php

namespace Laminas\Cache\Exception;

use OverflowException;

/** @final */
class OutOfSpaceException extends OverflowException implements ExceptionInterface
{
}
