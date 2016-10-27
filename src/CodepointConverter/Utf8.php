<?php

namespace MLocati\IDNA\CodepointConverter;

use MLocati\IDNA\Exception\InvalidCodepoint;
use MLocati\IDNA\Exception\InvalidCharacter;

/**
 * Convert an Unicode Code Point to/from an character in UTF-8 encoding.
 */
class Utf8 extends CodepointConverter
{
    /**
     * {@inheritdoc}
     *
     * @see CodepointConverter::getMinBytesPerCharacter()
     */
    protected function getMinBytesPerCharacter()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     *
     * @see CodepointConverter::getMaxBytesPerCharacter()
     */
    protected function getMaxBytesPerCharacter()
    {
        return 4;
    }

    /**
     * {@inheritdoc}
     *
     * @see CodepointConverter::codepointToCharacterDo()
     */
    protected function codepointToCharacterDo($codepoint)
    {
        if ($codepoint <= 0x7F) {
            $result = chr($codepoint);
        } elseif ($codepoint <= 0xFFFF) {
            // Basic Multilingual Plane: \u followed by four hexadecimal digits that encode the character's code point
            $u = '"\u'.substr('000'.dechex($codepoint), -4).'"';
            $result = @json_decode($u);
            if (!is_string($result)) {
                throw new InvalidCodepoint($codepoint);
            }
        } else {
            // Determine the UTF-16 surrogate pair
            $delta = $codepoint - 0x10000;
            $high = ($delta >> 10) | 0xD800;
            $low = ($delta & 0x3FF) | 0xDC00;
            $u = '"\u'.substr('000'.dechex($high), -4).'\u'.substr('000'.dechex($low), -4).'"';
            $result = @json_decode($u);
            if (!is_string($result)) {
                throw new InvalidCodepoint($codepoint);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see CodepointConverter::characterToCodepointDo()
     */
    protected function characterToCodepointDo($character)
    {
        if (!isset($character[1])) {
            $result = ord($character);
            if ($result > 0x7F) {
                throw new InvalidCharacter($character);
            }
        } elseif (isset($character[4])) {
            throw new InvalidCharacter($character);
        } else {
            $result = null;
            $s = @json_encode($character);
            if ($s && isset($s[7]) && strpos($s, '"\u') === 0) {
                $s = substr($s, 3, -1);
                $chunks = [];
                foreach (explode('\u', $s) as $i) {
                    if (strlen($i) !== 4) {
                        $chunks = null;
                        break;
                    }
                    $chunks[] = hexdec($i);
                }
                if ($chunks !== null) {
                    switch (count($chunks)) {
                        case 1:
                            $result = $chunks[0];
                            break;
                        case 2:
                            if ($chunks[0] >= 0xD800 && $chunks[1] >= 0xDC00) {
                                $result = 0x10000 + (($chunks[0] & ~0xD800) << 10) + ($chunks[1] & ~0xDC00);
                            }
                            break;
                    }
                }
            }
            if ($result === null) {
                throw new InvalidCharacter($character);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see CodepointConverter::stringToCharacters()
     */
    public function stringToCharacters($string)
    {
        $string = (string) $string;
        if ($string === '') {
            $result = [];
        } else {
            $result = false;
            if (function_exists('preg_split')) {
                $chars = @preg_split('//u', (string) $string, null, PREG_SPLIT_NO_EMPTY);
                if ($chars !== false) {
                    $result = $chars;
                }
            }
            if ($result === false) {
                $result = parent::stringToCharacters($string);
            }
        }

        return $result;
    }
}
