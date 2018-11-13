<?php

namespace MLocati\IDNA\Tests;

use Exception;
use MLocati\IDNA\CodepointConverter\Utf8;
use MLocati\IDNA\DomainName;
use PHPUnit\Framework\TestCase;

class DomainNameTest extends TestCase
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
        return array(
            array('GitHub.com', 'github.com', 'github.com'),
            array('faß.de', 'faß.de', 'xn--fa-hia.de', 'fass.de', 'fass.de'),
        );
    }

    /**
     * @dataProvider domainNameProvider
     */
    public function testDomainNameFromName($originalName, $normalizedName, $punycode, $deviatedName = '', $deviatedPunycode = '')
    {
        $domainName = DomainName::fromName($originalName, static::$codepointConverter);
        $this->assertSame($normalizedName, $domainName->getName());
        $this->assertSame($punycode, $domainName->getPunycode());
        $this->assertSame($deviatedName !== '', $domainName->isDeviated());
        $this->assertSame($deviatedName, $domainName->getDeviatedName());
        $this->assertSame($deviatedPunycode, $domainName->getDeviatedPunycode());
    }

    /**
     * @dataProvider domainNameProvider
     */
    public function testDomainNameFromPunycode($originalName, $normalizedName, $punycode, $deviatedName = '', $deviatedPunycode = '')
    {
        $domainName = DomainName::fromPunycode($punycode, static::$codepointConverter);
        $this->assertSame($normalizedName, $domainName->getName());
        $this->assertSame($punycode, $domainName->getPunycode());
        $this->assertSame($deviatedName !== '', $domainName->isDeviated());
        $this->assertSame($deviatedName, $domainName->getDeviatedName());
        $this->assertSame($deviatedPunycode, $domainName->getDeviatedPunycode());
    }

    public function invalidDomainsProvider()
    {
        return array(
            array('email@example.com', 'MLocati\IDNA\Exception\InvalidDomainNameCharacters', 'The domain name contains an invalid character: @'),
            array('http://www.domain.com', 'MLocati\IDNA\Exception\InvalidDomainNameCharacters', "The domain name contains 2 invalid characters:\n:\n/"),
            array("www\0.domain.com", 'MLocati\IDNA\Exception\InvalidDomainNameCharacters'),
            array("\xFF", 'MLocati\IDNA\Exception\InvalidString'),
            array("\xFF\xFF\xFF\xFF", 'MLocati\IDNA\Exception\InvalidString'),
        );
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
