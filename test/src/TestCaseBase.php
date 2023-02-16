<?php

namespace MLocati\IDNA\Test;

use PHPUnit\Framework\TestCase;

abstract class TestCaseBase extends TestCase
{
    /**
     * Override this method to implement the PHPUnit "setUpBeforeClassBase" method.
     */
    protected static function setUpBeforeClassBase()
    {

    }

    /**
     * Override this method to implement the PHPUnit "setUp" method.
     */
    protected function setUpBase()
    {
    }

    /**
     * Override this method to implement the PHPUnit "tearDown" method.
     */
    protected function tearDownBase()
    {
    }
}
