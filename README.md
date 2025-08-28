# PHPUnit Coverage Check

[![Build Status](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/?branch=master)
[![Continuous Integration](https://github.com/ericsizemore/phpunit-coverage-check/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ericsizemore/phpunit-coverage-check/actions/workflows/continuous-integration.yml)
[![SymfonyInsight](https://insight.symfony.com/projects/78420c3f-1f4e-4c79-b684-03887756083b/mini.svg)](https://insight.symfony.com/projects/78420c3f-1f4e-4c79-b684-03887756083b)
[![Type Coverage](https://shepherd.dev/github/ericsizemore/phpunit-coverage-check/coverage.svg)](https://shepherd.dev/github/ericsizemore/phpunit-coverage-check)
[![Psalm Level](https://shepherd.dev/github/ericsizemore/phpunit-coverage-check/level.svg)](https://shepherd.dev/github/ericsizemore/phpunit-coverage-check)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=ericsizemore_phpunit-coverage-check&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=ericsizemore_phpunit-coverage-check)
[![Latest Stable Version](https://img.shields.io/packagist/v/esi/phpunit-coverage-check.svg)](https://packagist.org/packages/esi/phpunit-coverage-check)
[![Downloads per Month](https://img.shields.io/packagist/dm/esi/phpunit-coverage-check.svg)](https://packagist.org/packages/esi/phpunit-coverage-check)
[![License](https://img.shields.io/packagist/l/esi/phpunit-coverage-check.svg)](https://packagist.org/packages/esi/phpunit-coverage-check)

[PHPUnit Coverage Check](http://github.com/ericsizemore/phpunit-coverage-check/) - Check the code coverage using the clover report of PHPUnit.

This library will read the clover XML report from PHPUnit and calculate the coverage score. 
Based on the given threshold, the application will exit OK if the coverage is higher than the threshold or exit with code 1 if the coverage is lower than the threshold.

This library can be used in multiple ways:

* In your continuous deployment environment
* By adding it to a pre-commit hook
* Requiring it as a dev dependency in your `composer.json` and running it with a [composer script](https://getcomposer.org/doc/articles/scripts.md). 

See [GitHub Action](#github-action), [Installation](#installation) and [Usage](#usage) for more information.

> [!IMPORTANT]
> This project is not in any way an official ``PHPUnit`` project.
> Meaning it is not associated with, or endorsed by, the ``PHPUnit`` project or its author ``Sebastian Bergmann``.

### GitHub Action

A GitHub action is available and can be found in the [PHPUnit Coverage Check Action](https://github.com/ericsizemore/phpunit-coverage-check-action) repository, or on the [GitHub Marketplace](https://github.com/marketplace/actions/phpunit-coverage-check).

## Installation

### Composer

`PHPUnit Coverage Check` can be installed using Composer. Add this repository as a dev-dependency to the composer.json file.

```bash
$ composer require --dev esi/phpunit-coverage-check:^3.0
```

To use PHPUnit Coverage Check on an older version of PHP:
* PHP 8.1, use version 1.0.0 (and check 1.x's [readme](https://github.com/ericsizemore/phpunit-coverage-check/blob/1.x/README.md#usage) as usage is slightly different):
```bash
$ composer require --dev esi/phpunit-coverage-check:^1.0
```
* PHP 8.2, use version 2.0.0 (and check 2.x's [readme](https://github.com/ericsizemore/phpunit-coverage-check/blob/2.x/README.md#usage))
```bash
$ composer require --dev esi/phpunit-coverage-check:^2.0
```


### Phar

Download the `phpunit-coverage-check.phar` from an available release. It is recommended to check the signature when downloading the Phar from a GitHub Release (with `phpunit-coverage-check.phar.asc`).

```bash
# Adjust the URL based on the latest release
wget -O phpunit-coverage-check.phar "https://github.com/ericsizemore/phpunit-coverage-check/releases/download/3.0.0/phpunit-coverage-check.phar"
wget -O phpunit-coverage-check.phar.asc "https://github.com/ericsizemore/phpunit-coverage-check/releases/download/3.0.0/phpunit-coverage-check.phar.asc"

# Check that the signature matches
gpg --verify phpunit-coverage-check.phar.asc phpunit-coverage-check.phar

# Check the issuer (the ID can also be found from the previous command)
gpg --keyserver hkps://keys.openpgp.org --recv-keys F8367C6E9D7A7028292144AAC9D8B66FF3C06696

rm phpunit-coverage-check.phar.asc
chmod +x phpunit-coverage-check.phar
```

The Phar files of *PHPUnit Coverage Check* are signed with a public key associated to ``admin@secondversion.com.``.
The [`key(s) associated with this E-Mail address`](https://keys.openpgp.org/search?q=admin%40secondversion.com) can be queried at [`keys.openpgp.org`](https://keys.openpgp.org/).


#### Install with Phive

You can also install the *PHPUnit Coverage Check* Phar with `Phive`.

If not already using Phive, you can read more about it [here](https://phar.io/), also Phive [installation](https://phar.io/#Install) and [usage](https://phar.io/#Usage).


## Usage

There are two parameters that must be passed to return the code coverage.

1. The location of the clover XML file that PHPUnit has generated.
2. The coverage threshold that is acceptable or 'passing.' Min = 1, Max = 100.

Generate the `clover.xml` file by using PHPUnit:

```bash
$ php vendor/bin/phpunit --coverage-clover clover.xml
```

You can also add the coverage report generation to your PHPUnit configuration file (`phpunit.xml.dist` for example). You would need to add the following lines inside the `<coverage>` tag:

```xml
    <report>
        <clover outputFile="clover.xml"/>
    </report>
```

* For more information, see the [PHPUnit Documentation](https://docs.phpunit.de/en/10.5/).
* Information about the [configuration file](https://docs.phpunit.de/en/10.5/configuration.html) and [commandline options](https://docs.phpunit.de/en/10.5/textui.html#command-line-options).


### If installed with Composer

```bash
$ php vendor/bin/coverage-check /path/to/clover.xml 100
$ php vendor/bin/coverage-check /path/to/clover.xml 100 --only-percentage

# -O for only-percentage works as well
$ php vendor/bin/coverage-check /path/to/clover.xml 100 -O

# -F or show-files will display coverage per file, formatted in a table
$ php vendor/bin/coverage-check /path/to/clover.xml 100 -F
```

You can use the Api directly if you wish. The `Esi\CoverageCheck\CoverageCheck::nonConsoleCall()` method will process and return the data, like how the console application displays it.

```php
    /**
     * Processes the coverage data with the given clover file and threshold, and returns the information
     * like how the Console application will.
     *
     * This is mainly useful for those that may wish to use this library outside the CLI/Console or PHAR.
     */
    public function nonConsoleCall(string $cloverFile, int $threshold = 100, bool $onlyPercentage = false): string
```

An example usage:

```php
use Esi\CoverageCheck\CoverageCheck;

$check = new CoverageCheck();
$results = $check->nonConsoleCall(__DIR__ . '/tests/fixtures/clover.xml', 90);

echo $results; // Total code coverage is 90.32%
```

### If using the Phar

```bash
$ php phpunit-coverage-check.phar /path/to/clover.xml 100
$ php phpunit-coverage-check.phar /path/to/clover.xml 100 --only-percentage

# -O for only-percentage works as well
$ php phpunit-coverage-check.phar /path/to/clover.xml 100 -O

# -F or show-files will display coverage per file, formatted in a table
$ php phpunit-coverage-check.phar /path/to/clover.xml 100 -F
```

With `--only-percentage` (or `-O`) enabled, the CLI command will only return the resulting coverage percentage.

#### --show-files

With `--show-files` (or `-F`), `--only-percentage` will be ignored. This option parses the clover file for coverage information for each file in the project/package, determine coverage, and display the information in a table.

For example:

##### Passing coverage example output

```bash
$ php coverage-check build/logs/clover.xml 90 -F

 ------------------------------------------------------------------- -------------------------- ----------
  File                                                                Elements (Covered/Total)   Coverage
 ------------------------------------------------------------------- -------------------------- ----------
  [...]\phpunit-coverage-check\src\Application.php                    12/12                      100.00%
  [...]\phpunit-coverage-check\src\Command\CoverageCheckCommand.php   94/94                      100.00%
  [...]\phpunit-coverage-check\src\CoverageCheck.php                  80/80                      100.00%
  [...]\phpunit-coverage-check\src\Style\CoverageCheckStyle.php       12/12                      100.00%
  [...]\phpunit-coverage-check\src\Utils.php                          39/39                      100.00%
 ------------------------------------------------------------------- -------------------------- ----------
  Overall Totals                                                      237/237                    100.00%
 ------------------------------------------------------------------- -------------------------- ----------
```

##### Mixed pass/fail coverage example output

```bash
$ php coverage-check tests/fixtures/clover.xml 90 -F

 ----------------------------- -------------------------- ----------
  File                          Elements (Covered/Total)   Coverage
 ----------------------------- -------------------------- ----------
  /tmp/Example/String.php       36/38                      94.74%
  /tmp/Example/StringList.php   20/24                      83.33%
 ----------------------------- -------------------------- ----------
  Overall Totals                56/62                      89.04%
 ----------------------------- -------------------------- ----------
```

#### --table-width

The `--table-width` (or `-W`) option will only have an affect if used with the `--show-files` option. The only requirement for this particular option is that you must pass an integer 
value. For example:

```bash
$ php coverage-check tests/fixtures/clover.xml 90 -F -W 120

# ...or
$ php coverage-check tests/fixtures/clover.xml 90 --show-files --table-width 120
```

## About

### Requirements

- PHPUnit Coverage Check works with PHP 8.3.0 or above.
- For PHP 8.2 please use [v2.0.x](https://github.com/ericsizemore/phpunit-coverage-check/tree/2.x) of PHPUnit Coverage Check.
- For PHP 8.1 please use [v1.0.x](https://github.com/ericsizemore/phpunit-coverage-check/tree/1.x) of PHPUnit Coverage Check.


### Credits

This library is a fork of/based upon [rregeer/phpunit-coverage-check](https://github.com/richardregeer/phpunit-coverage-check/) by [Richard Regeer](https://github.com/richardregeer).

Most of this library has been rewritten from the ground up, to replace and improve the majority of the original library. The overall idea, and key pieces of the calculation, are thanks to the original library. 
Many thanks and much appreciation to Richard Regeer for his wonderful work.

- Author: [Eric Sizemore](https://github.com/ericsizemore)
- Thanks to [Richard Regeer](https://github.com/richardregeer) for the original `phpunit-coverage-check` library this library is forked from.
- Thanks to [SensioLabs Security Checker](https://github.com/sensiolabs/security-checker) for inspiration in creating my implementation of the `coverage:check` command.
- Thanks to [Box](https://github.com/box-project/box) for their wonderful project that makes Phar generation a breeze.
- Thanks to [all Contributors](https://github.com/ericsizemore/phpunit-coverage-check/contributors).


### Contributing

See [CONTRIBUTING](./CONTRIBUTING.md).

Bugs and feature requests are tracked on [GitHub](https://github.com/ericsizemore/phpunit-coverage-check/issues).

### Contributor Covenant Code of Conduct

See [CODE_OF_CONDUCT.md](./CODE_OF_CONDUCT.md)

### Backward Compatibility Promise

See [backward-compatibility.md](./backward-compatibility.md) for more information on Backwards Compatibility.

### Changelog

See the [CHANGELOG](./CHANGELOG.md) for more information on what has changed recently.

### License

See the [LICENSE](./LICENSE) for more information on the license that applies to this project.

### Security

See [SECURITY](./SECURITY.md) for more information on the security disclosure process.
