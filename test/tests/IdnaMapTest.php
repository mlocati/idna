<?php

namespace MLocati\IDNA\Tests;

use MLocati\IDNA\CodepointConverter\CodepointConverterInterface;
use MLocati\IDNA\IdnaMap;
use PHPUnit_Framework_TestCase;

class IdnaMapTest extends PHPUnit_Framework_TestCase
{
    protected static $idnaMapError = 'Not initialized';

    public static function setUpBeforeClass()
    {
        if (!class_exists(IdnaMap::class, true)) {
            static::$idnaMapError = 'The IdnaMap can\'t be found (you may need to create it with the create-idnamap command)';
        } elseif (!is_callable([IdnaMap::class, 'isDisallowed'])) {
            static::$idnaMapError = 'The IdnaMap must be created with Disallowed support (--debug option of the create-idnamap command)';
        } else {
            static::$idnaMapError = null;
        }
    }

    public function allCodepointsAreCoveredProvider()
    {
        $data = [];
        if (static::$idnaMapError !== null) {
            $data[] = [0, 0, true];
        } else {
            $min = CodepointConverterInterface::MIN_CODEPOINT;
            $max = CodepointConverterInterface::MAX_CODEPOINT;
            $perStep = (int) max(10, ($max - $min + 1) / 1000);
            foreach ([true, false] as $useSTD3ASCIIRules) {
                for ($start = $min; $start <= $max; $start += $perStep) {
                    $end = min($max, $start + $perStep);
                    $data[] = [$start, $end, $useSTD3ASCIIRules];
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider allCodepointsAreCoveredProvider
     */
    public function testAllCodepointsAreCoveredProvider($from, $to, $useSTD3ASCIIRules)
    {
        if (static::$idnaMapError !== null) {
            $this->markTestSkipped(static::$idnaMapError);
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
