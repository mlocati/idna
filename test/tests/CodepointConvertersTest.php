<?php

namespace MLocati\IDNA\Tests;

use MLocati\IDNA\CodepointConverter\Utf8;
use PHPUnit\Framework\TestCase;

class CodepointConvertersTest extends TestCase
{
    public function specificCodepointsProvider()
    {
        $result = array(
            array(0x0000, "\0"),
            array(0x000a, "\n"),
            array(0x000d, "\r"),
        );
        $data = @file_get_contents(dirname(__DIR__).'/assets/utf8-symbols.bin');
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $chunks = explode("\t", $line, 2);
            $chunks[0] = hexdec(substr($chunks[0], 2));
            $result[] = $chunks;
        }

        return $result;
    }
    /**
     * @dataProvider specificCodepointsProvider
     */
    public function testSpecificCodepoints($codepoint, $character)
    {
        $shownCP = dechex($codepoint);
        if (strlen($shownCP) < 4) {
            $shownCP = substr('000'.$shownCP, -4);
        }
        $shownCP = '0x'.$shownCP;
        $converter = new Utf8();
        $this->assertSame($character, $converter->codepointToCharacter($codepoint), "Converting code point $shownCP to character '$character'");
        $this->assertSame($codepoint, $converter->characterToCodepoint($character), "Converting char '$character' to code point $shownCP");
    }
}
