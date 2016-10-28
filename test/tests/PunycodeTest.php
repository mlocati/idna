<?php

namespace MLocati\IDNA\Tests;

use MLocati\IDNA\CodepointConverter\CodepointConverterInterface;
use MLocati\IDNA\CodepointConverter\Utf8;
use MLocati\IDNA\Punycode;
use PHPUnit_Framework_TestCase;

class PunycodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CodepointConverterInterface
     */
    protected static $codepointConverter;

    public static function setUpBeforeClass()
    {
        static::$codepointConverter = new Utf8();
    }

    public function punycodeProvider()
    {
        $result = [];
        $data = @file_get_contents(dirname(__DIR__).'/assets/punycode.bin');
        $lines = explode("\n", $data);
        $test = [];
        foreach ($lines as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $test[] = $line;
            if (count($test) === 2) {
                $result[] = $test;
                $test = [];
            }
        }
        if (!empty($test)) {
            $result[] = $test;
        }

        return $result;
    }

    /**
     * @dataProvider punycodeProvider
     */
    public function testPunycode($extended, $punycode)
    {
        $out = '';
        $this->assertSame(
            strtolower($punycode),
            Punycode::encodeDomainName(self::$codepointConverter->stringToCodepoints($extended))
        );
        $this->assertSame(
            self::$codepointConverter->stringToCodepoints(mb_strtolower($extended, 'UTF-8')),
            Punycode::decodeDomainName($punycode)
        );
    }
}
