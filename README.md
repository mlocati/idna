[![Tests](https://github.com/mlocati/idna/actions/workflows/tests.yml/badge.svg)](https://github.com/mlocati/idna/actions/workflows/tests.yml)

# International Domain Names PHP library


## Introduction & terminology

Historically, we've been able to use domain names composed only by [ASCII characters](https://en.wikipedia.org/wiki/ASCII) (for instance: `www.example.com`).

A new technique, called **Internationalized Domain Names** (***IDN*** for short), allows you to use most of the [Unicode characters](https://en.wikipedia.org/wiki/Universal_Character_Set_characters), so that you can have for instance `www.例.中国`.

To grant compatibility with all the existing software that makes internet work, domain names containing non-ASCII characters are represented in **Punycode**, a special format that uses ASCII-only characters.


## Mapping

The generation of Punycode starting from an IDN should be **case insensitive**: browsing to `www.example.com` should be the same as browsing to `www.Example.COM`.

In PHP, converting strings to lower case is as easy as calling `strtolower`, but this function does not work with characters outside the ASCII characters (in fact, it may mess up the IDN names).
If you have the `mbstring` PHP extension, you may think to use the `mb_strtolower` PHP function it offers.

By the way, even `mb_strtolower` isn't a good choice, for these reasons:

1. the `mbstring` PHP extension may not be available
2. the `mb_strtolower` behaviour changes across different PHP versions (for instance, `Ԩ` is correctly converted to `ԩ` for PHP 7.0, but prior versions kept `Ԩ`)
3. `mb_strtolower` does not translate a lot of Unicode characters that are suggested by the standards

Unicode offers a [mapping table](http://www.unicode.org/Public/idna/latest/IdnaMappingTable.txt) with the recommended mapping (for instance, case normalization like `A` to `a`, but also `。` to `.`).


## Deviation

There are two standards that define the mapping that should be applied to IDN, IDNA2003 and IDNA2008.
IDNA2008 is backward compatible with IDNA2003, but there are some incompatible differences.

For instance, IDNA2003 required that `ß` mapped to `ss`, whereas IDNA2008 allows the usage of `ß`. So, older browsers and client softwares resolved `www.schloß.com` to the Punycode corresponding to `www.schloss.com`, whereas newer browsers resolve it to the Punycode of `www.schloß.com`.  

Since the resulting Punycode is different (it's called ***deviation***), this lead to big security issues, and you *need* to know that a domain name is deviated.


## Advantages of this library

- no dependencies from any PHP extension
- not dependent from any other PHP library
- consistency across different PHP versions
- results are granted to follow the standards (it's not just a bare *multibyte to punycode* conversion library)
- designed with speed in mind
- compatible with any PHP version ranging from PHP 5.3 to the most recent PHP versions (8.2 at the time of writing this)


## Sample usage

```php
require_once 'autoload.php'; // Not required if you use composer

$domain = \MLocati\IDNA\DomainName::fromName('www。schloß.COM');

echo "Name: ", $domain->getName(), "\n";
echo "Punycode: ", $domain->getPunycode(), "\n";
echo "Deviated: ", $domain->isDeviated() ? 'yes' : 'no', "\n";
echo "Deviated Name: ", $domain->getDeviatedName(), "\n";
echo "Deviated Punycode: ", $domain->getDeviatedPunycode(), "\n";
```

output:

```
Name: www.schloß.com
Punycode: www.xn--schlo-pqa.com
Deviated: yes
Deviated Name: www.schloss.com
Deviated Punycode: www.schloss.com
```
