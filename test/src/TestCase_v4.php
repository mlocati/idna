<?php

namespace MLocati\IDNA\Test;

class TestCase_v4 extends TestCaseBase
{
    /**
     * {@inheritdoc}
     *
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    final public static function setUpBeforeClass()
    {
        static::setUpBeforeClassBase();
    }

    /**
     * {@inheritdoc}
     *
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    final public function setUp()
    {
        $this->setUpBase();
    }

    /**
     * {@inheritdoc}
     *
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    final public function tearDown()
    {
        $this->tearDownBase();
    }
}
