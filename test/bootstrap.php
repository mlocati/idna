<?php

require_once dirname(__DIR__).'/autoload.php';

if (class_exists('PHPUnit\Runner\Version') && version_compare(PHPUnit\Runner\Version::id(), '8') >= 0) {
    class_alias('MLocati\IDNA\Test\TestCase_v8', 'MLocati\IDNA\Test\TestCase');
} else {
    class_alias('MLocati\IDNA\Test\TestCase_v4', 'MLocati\IDNA\Test\TestCase');
}
