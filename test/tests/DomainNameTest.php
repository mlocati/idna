<?php

namespace MLocati\IDNA\Tests;

use Exception;
use MLocati\IDNA\CodepointConverter\Utf8;
use MLocati\IDNA\DomainName;
use MLocati\IDNA\Exception\InvalidDomainNameCharacters;
use MLocati\IDNA\Exception\InvalidString;
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

    public function invalidDomainsProvider()
    {
        return [
            ['email@example.com', InvalidDomainNameCharacters::class, 'The domain name contains an invalid character: @'],
            ['http://www.domain.com', InvalidDomainNameCharacters::class, "The domain name contains 2 invalid characters:\n:\n/"],
            ["www\0.domain.com", InvalidDomainNameCharacters::class],
            ["\xFF", InvalidString::class],
            ["\xFF\xFF\xFF\xFF", InvalidString::class],
        ];
    }
    /**
     * @dataProvider invalidDomainsProvider
     */
    public function testInvalidDomains($name, $exceptionClass, $message = null)
    {
        $exception = null;
        try {
            DomainName::fromName($name);
        } catch (Exception $x) {
            $exception = $x;
        }
        $this->assertNotNull($exception, "Domain name '$name' should not be valid");
        $this->assertInstanceOf($exceptionClass, $exception);
        if ($message !== null) {
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
