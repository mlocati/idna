<?php

namespace MLocati\IDNA\Test;

class TestCase_v8 extends TestCaseBase
{
    /**
     * {@inheritdoc}
     *
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    final public static function setUpBeforeClass(): void
    {
        static::setUpBeforeClassBase();
    }

    /**
     * {@inheritdoc}
     *
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    final public function setUp(): void
    {
        $this->setUpBase();
    }

    /**
     * {@inheritdoc}
     *
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    final public function tearDown(): void
    {
        $this->tearDownBase();
    }
}
