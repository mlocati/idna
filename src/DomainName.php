<?php

namespace MLocati\IDNA;

use MLocati\IDNA\CodepointConverter\CodepointConverterInterface;
use MLocati\IDNA\CodepointConverter\Utf8;
use MLocati\IDNA\Exception\InvalidDomainNameCharacters;

class DomainName
{
    /**
     * @var string
     */
    protected $originalName;

    /**
     * @var string
     */
    protected $mappedName;

    /**
     * @var string
     */
    protected $deviatedName;

    /**
     * @var CodepointConverterInterface
     */
    protected $codepointConverter;
    /**
     * @param string                      $name
     * @param CodepointConverterInterface $codepointConverter
     *
     * @throws InvalidDomainNameCharacters
     */
    public function __construct($name, CodepointConverterInterface $codepointConverter = null)
    {
        $this->originalName = $name;
        $this->codepointConverter = ($codepointConverter === null) ? new Utf8() : $codepointConverter;
        $codepoints = $this->codepointConverter->stringToCodepoints($name);
        $codepoints = $this->removeIgnored($codepoints);
        $codepoints = $this->applyMapping($codepoints);
        $codepoints = $this->removeIgnored($codepoints);
        $this->checkValid($codepoints);
        $mappedName = $this->codepointConverter->codepointsToString($codepoints);
        if ($mappedName === $this->originalName) {
            $this->mappedName = '';
        } else {
            $this->mappedName = $mappedName;
        }
        $deviatedCodepoints = $this->applyDeviations($codepoints);
        $deviatedName = $this->codepointConverter->codepointsToString($deviatedCodepoints);
        if ($deviatedName === $mappedName) {
            $this->deviatedName = '';
        } else {
            $this->deviatedName = $deviatedName;
        }
    }

    /**
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * @return bool
     */
    public function isMappedName()
    {
        return $this->mappedName !== '';
    }
    
    /**
     * @return string
     */
    public function getMappedName()
    {
        return $this->mappedName;
    }

    /**
     * @return bool
     */
    public function isDeviatedName()
    {
        return $this->deviatedName !== '';
    }

    /**
     * @return string
     */
    public function getDeviatedName()
    {
        return $this->deviatedName;
    }

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

    protected function checkValid(array $codepoints)
    {
        $invalidCodepoints = [];
        $invalidCharacters = [];
        foreach ($codepoints as $codepoint) {
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
        if (!empty($invalidCodepoints)) {
            throw new InvalidDomainNameCharacters($invalidCodepoints, ($invalidCharacters === null) ? '' : implode("\n", $invalidCharacters));
        }
    }

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

    protected function applyDeviations(array $codepoints)
    {
        $result = [];
        foreach ($codepoints as $codepoint) {
            $deviated = IdnaMap::getDeviation($codepoint);
            if ($deviated === null) {
                $result[] = $codepoint;
            } else {
                $result = array_merge($result, $deviated);
            }
        }

        return $result;
    }
}
