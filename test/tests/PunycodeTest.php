<?php

namespace MLocati\IDNA\Tests;

use MLocati\IDNA\CodepointConverter\CodepointConverterInterface;
use MLocati\IDNA\CodepointConverter\Utf8;
use MLocati\IDNA\Punycode;
use MLocati\IDNA\Test\TestCase;
use Exception;

class PunycodeTest extends TestCase
{
    /**
     * @var CodepointConverterInterface
     */
    protected static $codepointConverter;

    public static function setUpBeforeClassBase()
    {
        static::$codepointConverter = new Utf8();
    }

    public static function punycodeProvider()
    {
        $result = array();
        $data = @file_get_contents(dirname(__DIR__).'/assets/punycode.bin');
        $lines = explode("\n", $data);
        $test = array();
        foreach ($lines as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $test[] = $line;
            if (count($test) === 2) {
                $result[] = $test;
                $test = array();
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
        $extendedCodepoints = self::$codepointConverter->stringToCodepoints($extended);
        $this->assertSame(
            $punycode,
            Punycode::encodeDomainName($extendedCodepoints)
        );
        $this->assertSame(
            $extendedCodepoints,
            Punycode::decodeDomainName($punycode)
        );
        $upperCasePunicode = strtoupper($punycode);
        $this->assertSame(
            $extendedCodepoints,
            Punycode::decodeDomainName($upperCasePunicode)
        );
    }

    public static function invalidPunycodeProvider()
    {
        return array(
            array(''),
            array('.'),
            array('www..com'),
            array('This is invalid'),
            array('\xFF\xFF\xFF\xFF'),
            array('\xFF\xFF'),
            array('123456789-123456789-123456789-123456789-123456789-123456789-1234'),
            array('1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8.9.o.1.2.3.4.5.6.7.8'),
            array('123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.123456789.12345'),
            array('xn---'),
            array('xn--z'),
            array('xn--zzzzzzzzzzzzzzzzzzzzzzzzzz'),
        );
    }

    /**
     * @dataProvider invalidPunycodeProvider
     */
    public function testInvalidPunycode($punycode)
    {
        $exception = null;
        try {
            Punycode::decodeDomainName($punycode);
        }
        catch (Exception $x) {
            $exception = $x;
        }
        $this->assertNotNull($exception);
        $this->assertInstanceOf('MLocati\IDNA\Exception\InvalidPunycode', $exception);
    }
}
