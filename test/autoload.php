<?php

use PHPUnit\Framework\TestCase;

if (! class_exists('PHPUnit_Framework_TestCase')) {
    class_alias(TestCase::class, 'PHPUnit_Framework_TestCase');
}
