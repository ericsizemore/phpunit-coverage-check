# PHPUnit Coverage Check

[![Build Status](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/phpunit-coverage-check/?branch=master)
[![Tests](https://github.com/ericsizemore/phpunit-coverage-check/actions/workflows/tests.yml/badge.svg)](https://github.com/ericsizemore/phpunit-coverage-check/actions/workflows/tests.yml)
[![PHPStan](https://github.com/ericsizemore/phpunit-coverage-check/actions/workflows/main.yml/badge.svg)](https://github.com/ericsizemore/phpunit-coverage-check/actions/workflows/main.yml)

[![Latest Stable Version](https://img.shields.io/packagist/v/esi/phpunit-coverage-check.svg)](https://packagist.org/packages/esi/phpunit-coverage-check)
[![Downloads per Month](https://img.shields.io/packagist/dm/esi/phpunit-coverage-check.svg)](https://packagist.org/packages/esi/phpunit-coverage-check)
[![License](https://img.shields.io/packagist/l/esi/phpunit-coverage-check.svg)](https://packagist.org/packages/esi/phpunit-coverage-check)

[PHPUnit Coverage Check](http://github.com/ericsizemore/phpunit-coverage-check/) - Check the code coverage using the clover report of PHPUnit.

This php script will read the clover xml report from PHPUnit and calculate the coverage score. Based on the given threshold the script will exit ok if the coverage is higher than the threshold or exit with code 1 if the coverage is lower than the threshold.

This script can be used in your continuous deployment environment or for example added to a pre-commit hook.


## Acknowledgements/Credits

This library is a fork of/based upon [rregeer/phpunit-coverage-check](https://github.com/richardregeer/phpunit-coverage-check/) by [Richard Regeer](https://github.com/richardregeer).

Most of this library has been rewritten from the ground up, to replace and improve a majority of the original library. The overall idea, and key pieces of the calculation, are thanks to the original library. Many thanks and much appreciation to Richard Regeer for his wonderful work.

Please see [License](#license) and the [LICENSE](LICENSE) file for more information.

For more information on changes made in this library, in comparison to the original by Richard Regeer, please see the [CHANGELOG](CHANGELOG.md) file.

Phar generation is handled by using [Box](https://github.com/box-project/box). My implementation of the `coverage:check` command, with Symfony\Console, was inspired by [SensioLabs Security Checker](https://github.com/sensiolabs/security-checker).


## Important Note

This project is not in any way an official ``PHPUnit`` project. Meaning, that it is not associated with, or endorsed by, the ``PHPUnit`` project or its author ``Sebastian Bergmann``.


## Installation

### Composer

The script can be installed using composer. Add this repository as a dependency to the composer.json file.

```bash
$ composer require --dev esi/phpunit-coverage-check:^2.0
```

To use PHPUnit Coverage Check on PHP 8.1, use version 1.0.0:

```bash
$ composer require esi/phpunit-coverage-check:^1.0
```


### Phar

Download the `phpunit-coverage-check.phar` from an available release. It is recommended to check the signature when downloading the Phar from a GitHub Release (with `phpunit-coverage-check.phar.asc`).

```bash
# Adjust the URL based on the latest release
wget -O phpunit-coverage-check.phar "https://github.com/ericsizemore/phpunit-coverage-check/releases/download/1.0.0/phpunit-coverage-check.phar"
wget -O phpunit-coverage-check.phar.asc "https://github.com/ericsizemore/phpunit-coverage-check/releases/download/1.0.0/phpunit-coverage-check.phar.asc"

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

The script has 2 parameters that are mandatory to return the code coverage.

1. The location of the clover xml file, that's generated by PHPUnit.
2. The coverage threshold that is acceptable. Min = 1, Max = 100

Generate the `clover.xml` file by using PHPUnit:

```bash
$ php vendor/bin/phpunit --coverage-clover clover.xml
```

It's also possible to add the coverage report generation to your PHPUnit configuration file (`phpunit.xml.dist` for example). You would add to following lines to the xml file inside the `<coverage>` tag:

```xml
    <report>
        <clover outputFile="clover.xml"/>
    </report>
```

* For more information see the [PHPUnit Documentation](https://docs.phpunit.de/en/10.5/).
* Information about the [configuration file](https://docs.phpunit.de/en/10.5/configuration.html) and [commandline options](https://docs.phpunit.de/en/10.5/textui.html#command-line-options).


### If installed with Composer

```bash
$ php vendor/bin/coverage-check /path/to/clover.xml 100
$ php vendor/bin/coverage-check /path/to/clover.xml 100 --only-percentage
# -O for only-percentage works as well
$ php vendor/bin/coverage-check /path/to/clover.xml 100 -O
```

You can also use the Api directly if you wish. I created a function called `nonConsoleCall` that will process and return the data, similar to how the console application displays it.

```php
    /**
     * Processes the coverage data with the given clover file and threshold, and returns the information
     * similar to how the Console application will.
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
```

With `--only-percentage` (or `-O`) enabled, the CLI command will only return the resulting coverage percentage.


## About

### Requirements

- PHPUnit Coverage Check works with PHP 8.2.0 or above.


### Submitting bugs and feature requests

Bugs and feature requests are tracked on [GitHub](https://github.com/ericsizemore/phpunit-coverage-check/issues)

Issues are the quickest way to report a bug. If you find a bug or documentation error, please check the following first:

* That there is not an Issue already open concerning the bug
* That the issue has not already been addressed (within closed Issues, for example)


### Contributing

See [CONTRIBUTING](CONTRIBUTING.md)


### Backward Compatibility Promise

* See [backward-compatibility.md](backward-compatibility.md)


### Author

Eric Sizemore - <admin@secondversion.com> - <https://www.secondversion.com>


### License

PHPUnit Coverage Check is licensed under the MIT [License](LICENSE).