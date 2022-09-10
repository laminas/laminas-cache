<?php

declare(strict_types=1);

namespace Laminas\Cache\Exception;

use OverflowException;

class OutOfSpaceException extends OverflowException implements ExceptionInterface
{
}
