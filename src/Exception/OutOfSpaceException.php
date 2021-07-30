<?php

namespace Laminas\Cache\Exception;

use OverflowException;

class OutOfSpaceException extends OverflowException implements ExceptionInterface
{
}
