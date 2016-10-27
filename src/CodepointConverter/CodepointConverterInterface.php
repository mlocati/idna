<?php

namespace MLocati\IDNA\CodepointConverter;

use MLocati\IDNA\Exception\InvalidString;
use MLocati\IDNA\Exception\InvalidCodepoint;
use MLocati\IDNA\Exception\InvalidCharacter;

/**
 * Convert an Unicode Code Point to/from an character.
 */
interface CodepointConverterInterface
{
    /**
     * The minimum codepoint.
     *
     * @var int
     */
    const MIN_CODEPOINT = 0;

    /**
     * The maximum codepoint.
     *
     * @var int
     */
    const MAX_CODEPOINT = 0x10FFFF;

    /**
     * Check if a variable contains a valid Unicode Code Point.
     *
     * @param int|mixed $codepoint
     *
     * @return bool
     */
    public function isCodepointValid($codepoint);

    /**
     * Convert an UTF8-encoded character to its Unicode Code Point.
     *
     * @param string $character
     *
     * @return int
     *
     * @throws InvalidCharacter
     */
    public function characterToCodepoint($character);

    /**
     * Encode a list of code points to a list of characters.
     *
     * @param string[] $characters
     *
     * @return int[]
     *
     * @throws InvalidCharacter
     */
    public function charactersToCodepoints(array $characters);

    /**
     * Get the string starting from a list of characters.
     *
     * @param string[] $characters
     *
     * @return string
     */
    public function charactersToString(array $characters);

    /**
     * Convert a Unicode Code Point to an character with an implementation-specific encoding.
     *
     * @param int|mixed $codepoint
     *
     * @return string
     *
     * @throws InvalidCodepoint
     */
    public function codepointToCharacter($codepoint);

    /**
     * Encode a list of code points to a list of characters.
     *
     * @param int[] $codepoints
     *
     * @return string[]
     *
     * @throws InvalidCodepoint
     */
    public function codepointsToCharacters(array $codepoints);

    /**
     * Encode a list of code points to a string.
     *
     * @param int[] $codepoints
     *
     * @return string
     *
     * @throws InvalidCodepoint
     */
    public function codepointsToString(array $codepoints);

    /**
     * Get the character list of a string.
     *
     * @param string $string
     *
     * @return string[]
     *
     * @throws InvalidString
     */
    public function stringToCharacters($string);

    /**
     * Get the code point list of a string.
     *
     * @param string $string
     *
     * @return int[]
     *
     * @throws InvalidString
     */
    public function stringToCodepoints($string);
}
