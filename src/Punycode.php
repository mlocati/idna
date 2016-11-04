<?php

namespace MLocati\IDNA;

use MLocati\IDNA\CodepointConverter\USAscii;
use MLocati\IDNA\Exception\InvalidPunycode;
use MLocati\IDNA\Exception\InvalidString;

/**
 * Convert domain names to/from punycode.
 *
 * Part of this code has been freely taken from https://www.ietf.org/rfc/rfc3492.txt (written by Adam M. Costello)
 */
class Punycode
{
    /**
     * Size of the dictionary.
     *
     * @var int
     */
    const BOOTSTRING_BASE = 36;

    /**
     * Minimum threshold for the variable-length integers.
     *
     * @var int
     */
    const BOOTSTRING_TMIN = 1;

    /**
     * Maximum threshold for the variable-length integers.
     *
     * @var int
     */
    const BOOTSTRING_TMAX = 26;

    /**
     * The skew term for the bias adapation.
     *
     * @var int
     */
    const BOOTSTRING_SKEW = 38;

    /**
     * The damp value for the bias adaption.
     *
     * @var int
     */
    const BOOTSTRING_DAMP = 700;

    /**
     * The initial value of the bias of the variable-length integer threshold.
     *
     * @var int
     */
    const BOOTSTRING_INITIAL_BIAS = 72;

    /**
     * The first code point to be encoded.
     *
     * @var int
     */
    const BOOTSTRING_INITIAL_N = 128;

    /**
     * Separator between the basic code points and the other encoded code points.
     *
     * @var string
     */
    const DELIMITER = '-'; /* 0x2D */

    /**
     * Domain name label prefix if it contains some non-basic code points.
     *
     * @var string
     */
    const PREFIX = 'xn--';

    /**
     * The code point of the domain name labels separator.
     *
     * @var int
     */
    const LABEL_SEPARATOR = 0x2E;

    /**
     * The basic code points dictionary.
     *
     * @var string
     */
    const DICTIONARY = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Encode a list of codepoints representing a domain name to Punycode.
     *
     * @param int[] $codepoints
     *
     * @throws InvalidCodepoint
     *
     * @return string
     */
    public static function encodeDomainName(array $codepoints)
    {
        $labels = array();
        $currentLabel = null;
        foreach ($codepoints as $codepoint) {
            if ($codepoint === static::LABEL_SEPARATOR) {
                if ($currentLabel !== null) {
                    $labels[] = static::encodeDomainLabel($currentLabel);
                }
                $currentLabel = array();
            } elseif ($currentLabel === null) {
                $currentLabel = array($codepoint);
            } else {
                $currentLabel[] = $codepoint;
            }
        }
        if ($currentLabel !== null) {
            $labels[] = static::encodeDomainLabel($currentLabel);
        }

        return implode('.', $labels);
    }

    /**
     * Convert a domain name represented in punycode to Unicode code points.
     *
     * @param string $punycode
     *
     * @throws InvalidPunycode
     *
     * @return int[]
     */
    public static function decodeDomainName($punycode)
    {
        $result = array();
        $first = true;
        try {
            foreach (explode(chr(static::LABEL_SEPARATOR), (string) $punycode) as $label) {
                if ($first) {
                    $first = false;
                } else {
                    $result[] = static::LABEL_SEPARATOR;
                }
                $result = array_merge($result, static::decodeDomainLabel($label));
            }
        } catch (InvalidPunycode $x) {
            throw new InvalidPunycode($punycode);
        } catch (InvalidString $x) {
            throw new InvalidPunycode($punycode);
        }

        return $result;
    }

    /**
     * Encode a list of codepoints representing a domain name label to Punycode.
     *
     * @param int[] $codepoints
     *
     * @return string
     */
    protected static function encodeDomainLabel(array $codepoints)
    {
        // Split basic and non-basic code points
        $basicCodepoints = array();
        $extraCodepoints = array();
        foreach ($codepoints as $codepoint) {
            if ($codepoint < self::BOOTSTRING_INITIAL_N) {
                $basicCodepoints[] = $codepoint;
            } else {
                $extraCodepoints[] = $codepoint;
            }
        }
        // Handle the basic code points
        $numBasicCodepoints = count($basicCodepoints);
        if ($numBasicCodepoints === 0) {
            $result = '';
        } else {
            $usAscii = new USAscii();
            $result = strtolower($usAscii->codepointsToString($basicCodepoints));
        }
        if (!empty($extraCodepoints)) {
            if ($numBasicCodepoints > 0) {
                $result .= self::DELIMITER;
            }
            // Initialize the state
            $n = self::BOOTSTRING_INITIAL_N;
            $bias = self::BOOTSTRING_INITIAL_BIAS;
            $delta = 0;
            $h = $numBasicCodepoints;
            $extraCodepoints = array_unique($extraCodepoints);
            sort($extraCodepoints);
            $i = 0;
            $length = count($codepoints);
            $dictionary = self::DICTIONARY;
            // Main encoding loop
            while ($h < $length) {
                $m = $extraCodepoints[$i++];
                $delta += ($m - $n) * ($h + 1);
                $n = $m;
                foreach ($codepoints as $c) {
                    if ($c < $n || $c < self::BOOTSTRING_INITIAL_N) {
                        ++$delta;
                    }
                    if ($c === $n) {
                        // Represent delta as a generalized variable-length integer
                        for ($q = $delta, $k = self::BOOTSTRING_BASE; ; $k += self::BOOTSTRING_BASE) {
                            $t = self::threshold($k, $bias);
                            if ($q < $t) {
                                break;
                            }
                            $code = $t + (($q - $t) % (self::BOOTSTRING_BASE - $t));
                            $result .= $dictionary[$code];
                            $q = ($q - $t) / (self::BOOTSTRING_BASE - $t);
                        }
                        $result .= $dictionary[(int) $q];
                        $bias = self::adapt($delta, $h + 1, ($h === $numBasicCodepoints));
                        $delta = 0;
                        ++$h;
                    }
                }
                ++$delta;
                ++$n;
            }
            $result = self::PREFIX.$result;
        }

        return $result;
    }

    /**
     * Decode a domain name label from punycode to Unicode code points.
     *
     * @param string $label
     *
     * @throws InvalidPunycode
     * @throws InvalidString
     *
     * @return int[]
     */
    protected static function decodeDomainLabel($label)
    {
        $usAscii = new USAscii();
        if (stripos($label, self::PREFIX) !== 0) {
            $result = $usAscii->stringToCodepoints($label);
        } else {
            $input = substr($label, strlen(self::PREFIX));
            // Handle the basic code points
            $in = strrpos($input, self::DELIMITER);
            if ($in === false) {
                $result = array();
                $outputLength = 0;
                $in = 0;
            } else {
                $result = $usAscii->stringToCodepoints(strtolower(substr($input, 0, $in)));
                $outputLength = $in;
                ++$in;
            }
            // $in: the index of the next character to be consumed
            // Initialize the state
            $dictionary = self::DICTIONARY;
            $n = self::BOOTSTRING_INITIAL_N;
            $bias = self::BOOTSTRING_INITIAL_BIAS;
            $i = 0;
            $inputLength = strlen($input);
            // Main decoding loop
            while ($in < $inputLength) {
                // Decode a generalized variable-length integer into delta, which gets added to i.
                for ($oldi = $i, $w = 1, $k = self::BOOTSTRING_BASE; ; $k += self::BOOTSTRING_BASE) {
                    if ($in >= $inputLength) {
                        throw new InvalidPunycode($label);
                    }
                    $char = $input[$in];
                    if ($char >= 'A' && $char <= 'Z') {
                        $char = strtolower($char);
                    }
                    $digit = strpos(self::DICTIONARY, $char);
                    if ($digit === false) {
                        throw new InvalidPunycode($label);
                    }
                    ++$in;
                    $i += $digit * $w;
                    $t = self::threshold($k, $bias);
                    if ($digit < $t) {
                        break;
                    }
                    $w *= self::BOOTSTRING_BASE - $t;
                }
                $bias = self::adapt($i - $oldi, ++$outputLength, ($oldi === 0));
                $n += (int) ($i / $outputLength);
                $i %= $outputLength;
                array_splice($result, $i, 0, array($n));
                ++$i;
            }
        }

        return $result;
    }

    /**
     * Calculate the bias threshold to fall between BOOTSTRING_TMIN and BOOTSTRING_TMAX.
     *
     * @param int $k
     * @param int $bias
     *
     * @return int
     */
    protected static function threshold($k, $bias)
    {
        if ($k <= $bias) {
            return self::BOOTSTRING_TMIN;
        } elseif ($k >= $bias + self::BOOTSTRING_TMAX) {
            return self::BOOTSTRING_TMAX;
        } else {
            return $k - $bias;
        }
    }

    /**
     * Bias adaptation function.
     *
     * @param int $delta
     * @param int $numPoints
     * @param bool $firstTime
     *
     * @return int
     */
    protected static function adapt($delta, $numPoints, $firstTime)
    {
        $delta = $firstTime ?
            (int) $delta / self::BOOTSTRING_DAMP :
            $delta >> 1
        ;
        $delta += (int) ($delta / $numPoints);
        $check = ((self::BOOTSTRING_BASE - self::BOOTSTRING_TMIN) * self::BOOTSTRING_TMAX) >> 1;
        $multiplier = 1.0 / (self::BOOTSTRING_BASE - self::BOOTSTRING_TMIN);
        for (
            $k = 0;
            $delta > $check;
            $k = $k + self::BOOTSTRING_BASE
        ) {
            $delta = (int) ($delta * $multiplier);
        }
        $k = $k + (int) (((self::BOOTSTRING_BASE - self::BOOTSTRING_TMIN + 1) * $delta) / ($delta + self::BOOTSTRING_SKEW));

        return $k;
    }
}
