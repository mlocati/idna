<?php

namespace MLocati\IDNA;

use MLocati\IDNA\CodepointConverter\CodepointConverterInterface;
use MLocati\IDNA\CodepointConverter\Utf8;
use MLocati\IDNA\Exception\InvalidDomainNameCharacters;
use MLocati\IDNA\Exception\InvalidString;

class DomainName
{
    /**
     * The original domain name as received from input.
     *
     * @var string
     */
    protected $originalName;

    /**
     * The domain name with normalized (ie valid) characters.
     *
     * @var string
     */
    protected $normalizedName;

    /**
     * The punycode of the normalized name.
     *
     * @var string
     */
    protected $punycode;

    /**
     * The domain name deviated from IDNA2003 to IDNA2008 (non empty only if different).
     *
     * @var string
     */
    protected $deviatedName;

    /**
     * The punycode of the deviated name.
     *
     * @var string
     */
    protected $deviatedPunycode;

    /**
     * The converter to be used to convert characters to/from Unicode code points.
     *
     * @var CodepointConverterInterface
     */
    protected $codepointConverter;

    /**
     * Initializes the instance.
     */
    protected function __construct(CodepointConverterInterface $codepointConverter = null)
    {
        $this->originalName = '';
        $this->normalizedName = '';
        $this->punycode = '';
        $this->deviatedName = '';
        $this->deviatedPunycode = '';
        $this->codepointConverter = ($codepointConverter === null) ? new Utf8() : $codepointConverter;
    }

    /**
     * Creates a new instance of the class starting from a string containing the domain name.
     *
     * @param string $name The domain name
     * @param CodepointConverterInterface $codepointConverter The converter to handle the name (defaults to UTF-8)
     *
     * @throws InvalidString Throws an InvalidString exception if $name contains characters outside the encoding handled by $codepointConverter
     * @throws InvalidDomainNameCharacters Throws an InvalidDomainNameCharacters if $name contains characters marked as Invalid by the IDNA Mapping table
     *
     * @return static
     */
    public static function fromName($name, CodepointConverterInterface $codepointConverter = null)
    {
        $result = new static($codepointConverter);
        $result->originalName = $name;
        $codepoints = $result->codepointConverter->stringToCodepoints($name);
        $codepoints = $result->removeIgnored($codepoints);
        $codepoints = $result->applyMapping($codepoints);
        $result->checkValid($codepoints);
        $result->normalizedName = $result->codepointConverter->codepointsToString($codepoints);
        $result->punycode = Punycode::encodeDomainName($codepoints);
        $deviatedCodepoints = $result->applyDeviations($codepoints);
        if ($deviatedCodepoints !== null) {
            $result->deviatedName = $result->codepointConverter->codepointsToString($deviatedCodepoints);
            $result->deviatedPunycode = Punycode::encodeDomainName($deviatedCodepoints);
        }

        return $result;
    }

    /**
     * Get the original domain name as received from input.
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Check if the original name needed remapping to build a normalized domain name.
     *
     * @return bool
     */
    public function neededRemapping()
    {
        return $this->normalizedName !== $this->originalName;
    }

    /**
     * Get the domain name with normalized (ie valid) characters.
     *
     * @return string
     */
    public function getNormalizedName()
    {
        return $this->normalizedName;
    }

    /**
     * Get the punycode (of the normalized name).
     *
     * @return string
     */
    public function getPunycode()
    {
        return $this->punycode;
    }

    /**
     * Check if the domain name deviated from IDNA2003 to IDNA2008.
     *
     * @return bool
     */
    public function isDeviated()
    {
        return $this->deviatedName !== '';
    }

    /**
     * Get the domain name deviated from IDNA2003 to IDNA2008 (empty if it does not deviate).
     *
     * @return string
     */
    public function getDeviatedName()
    {
        return $this->deviatedName;
    }

    /**
     * Get the the punycode of the deviated name.
     *
     * @return string
     */
    public function getDeviatedPunycode()
    {
        return $this->deviatedPunycode;
    }

    /**
     * Remove ignored code points.
     *
     * @param int[] $codepoints
     *
     * @return int[]
     */
    protected function removeIgnored(array $codepoints)
    {
        $result = [];
        foreach ($codepoints as $codepoint) {
            if (!IdnaMap::isIgnored($codepoint)) {
                $result[] = $codepoint;
            }
        }

        return $result;
    }

    /**
     * Map code points accordingly to the IDNA Mapping table.
     *
     * @param int[] $codepoints
     *
     * @return int[]
     */
    protected function applyMapping(array $codepoints)
    {
        $result = [];
        foreach ($codepoints as $codepoint) {
            $mapped = IdnaMap::getMapped($codepoint);
            if ($mapped === null) {
                $result[] = $codepoint;
            } else {
                $result = array_merge($result, $mapped);
            }
        }

        return $result;
    }

    /**
     * Check that a list of code points does not contain values marked as invalid by the IDNA Mapping table.
     *
     * @param int[] $codepoints
     *
     * @throws InvalidDomainNameCharacters
     */
    protected function checkValid(array $codepoints)
    {
        $invalidCodepoints = [];
        $invalidCharacters = [];
        foreach ($codepoints as $codepoint) {
            if (IdnaMap::getDeviation($codepoint) === null) {
                if (IdnaMap::isValid($codepoint, [IdnaMap::EXCLUDE_ALWAYS, IdnaMap::EXCLUDE_CURRENT]) !== true) {
                    if (!in_array($codepoint, $invalidCodepoints)) {
                        $invalidCodepoints[] = $codepoint;
                        if ($invalidCharacters !== null) {
                            try {
                                $invalidCharacters[] = $this->codepointConverter->codepointToCharacter($codepoint);
                            } catch (\Exception $x) {
                                $invalidCharacters = null;
                            }
                        }
                    }
                }
            }
        }
        if (!empty($invalidCodepoints)) {
            throw new InvalidDomainNameCharacters($invalidCodepoints, ($invalidCharacters === null) ? '' : implode("\n", $invalidCharacters));
        }
    }

    /**
     * Map the code points marked as deviated from IDNA2003 to IDNA2008.
     *
     * @param int[] $codepoints The code points with values in the IDNA2008 valid range
     *
     * @return int[]|null The code points with values in the IDNA2003 valid range. If no deviated code point is found, you'll have null back
     */
    protected function applyDeviations(array $codepoints)
    {
        $someFound = false;
        $result = [];
        foreach ($codepoints as $codepoint) {
            $deviated = IdnaMap::getDeviation($codepoint);
            if ($deviated === null) {
                $result[] = $codepoint;
            } else {
                $someFound = true;
                $result = array_merge($result, $deviated);
            }
        }

        return $someFound ? $result : null;
    }
}
