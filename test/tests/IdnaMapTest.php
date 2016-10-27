<?php

namespace MLocati\IDNA\Tests;

use PHPUnit_Framework_TestCase;
use MLocati\IDNA\IdnaMap;
use MLocati\IDNA\CodepointConverter\CodepointConverterInterface;

class IdnaMapTest extends PHPUnit_Framework_TestCase
{
    public function allCodepointsAreCoveredProvider()
    {
        return [
            [CodepointConverterInterface::MIN_CODEPOINT, CodepointConverterInterface::MAX_CODEPOINT, true],
            [CodepointConverterInterface::MIN_CODEPOINT, CodepointConverterInterface::MAX_CODEPOINT, false],
        ];
    }
    /**
     * @dataProvider allCodepointsAreCoveredProvider
     */
    public function testAllCodepointsAreCoveredProvider($from, $to, $useSTD3ASCIIRules)
    {
        if (!class_exists(IdnaMap::class, true)) {
            $this->markTestSkipped('The IdnaMap can\'t be found (you may need to create it with the create-idnamap command)');
        }
        if (!is_callable([IdnaMap::class, 'isDisallowed'])) {
            $this->markTestSkipped('The IdnaMap must be created with Disallowed support (--debug option of the create-idnamap command)');
        }
        for ($codepoint = $from; $codepoint <= $to; ++$codepoint) {
            $covered = false;
            if (IdnaMap::isDisallowed($codepoint, $useSTD3ASCIIRules)) {
                $covered = true;
            } elseif (IdnaMap::getMapped($codepoint, $useSTD3ASCIIRules) !== null) {
                $covered = true;
            } elseif (IdnaMap::getDeviation($codepoint) !== null) {
                $covered = true;
            } elseif (IdnaMap::isIgnored($codepoint)) {
                $covered = true;
            } elseif (IdnaMap::isValid($codepoint, [], $useSTD3ASCIIRules)) {
                $covered = true;
            }
            $this->assertTrue($covered, "Check that code point $codepoint (0x".dechex($codepoint).') is covered');
        }
    }
}
