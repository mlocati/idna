<?php

namespace MLocati\IDNA\Tests;

use MLocati\IDNA\CodepointConverter\Utf8;
use MLocati\IDNA\DomainName;
use PHPUnit_Framework_TestCase;

class DomainNameTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CodepointConverterInterface
     */
    protected static $codepointConverter;

    public static function setUpBeforeClass()
    {
        static::$codepointConverter = new Utf8();
    }

    public function domainNameProvider()
    {
        return [
            ['GitHub.com', 'github.com', 'github.com'],
            ['faß.de', 'faß.de', 'xn--fa-hia.de', 'fass.de', 'fass.de'],
        ];
    }

    /**
     * @dataProvider domainNameProvider
     */
    public function testDomainNameFromName($originalName, $normalizedName, $punycode, $deviatedName = '', $deviatedPunycode = '')
    {
        $domainName = DomainName::fromName($originalName, static::$codepointConverter);
        $this->assertSame($originalName, $domainName->getOriginalName());
        $this->assertSame($normalizedName, $domainName->getNormalizedName());
        $this->assertSame($punycode, $domainName->getPunycode());
        $this->assertSame($deviatedName !== '', $domainName->isDeviated());
        $this->assertSame($deviatedName, $domainName->getDeviatedName());
        $this->assertSame($deviatedPunycode, $domainName->getDeviatedPunycode());
    }
}
