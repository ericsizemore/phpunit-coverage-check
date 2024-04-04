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


## Important Note

This project is not in any way an official ``PHPUnit`` project. Meaning, that it is not associated with, or endorsed by, the ``PHPUnit`` project or its author ``Sebastian Bergmann``.


## Something I'd like to note...

Symfony in general, and Symfony\Console specifically, is not my forte; neither are Phar files.

With that being said, Phar generation is handled by using [Box](https://github.com/box-project/box). My implementation of the `coverage:check` command, with Symfony\Console, was inspired by [SensioLabs Security Checker](https://github.com/sensiolabs/security-checker).

If you run into any issues, have any recommendations, etc. - please reach out [here](https://github.com/ericsizemore/phpunit-coverage-check/issues). I'm open to everything!


## Installation

### Composer

The script can be installed using composer. Add this repository as a dependency to the composer.json file.

```bash
$ composer require --dev esi/phpunit-coverage-check
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
$ php vendor/bin/coverage-check coverage:check /path/to/clover.xml 100
$ php vendor/bin/coverage-check coverage:check /path/to/clover.xml 100 --only-percentage
```

You can also use the Api directly if you wish. I created a function called `nonConsoleCall` that will process and return the data, similar to how the console application displays it.

```php
    /**
     * Processes the coverage data with the given clover file and threshold, and returns the information
     * similar to how the Console application will.
     *
     * This is mainly useful for those that may wish to use this library outside of the CLI/Console or PHAR.
     */
    public function nonConsoleCall(string $cloverFile, int $threshold = 100, bool $onlyPercentage = false): string
```

An example usage:

```php
use Esi\CoverageCheck\CoverageCheck;

$check = new CoverageCheck();
$results = $check->nonConsoleCall(__DIR__ . '/tests/fixtures/clover.xml', 90);

echo $results; // Total code coverage is 90.32 % - OK!
```


### If using the Phar

```bash
$ php phpunit-coverage-check.phar coverage:check /path/to/clover.xml 100
$ php phpunit-coverage-check.phar coverage:check /path/to/clover.xml 100 --only-percentage
```

With `--only-percentage` enabled, the CLI command will only return the resulting coverage percentage.


## About

### Requirements

- PHPUnit Coverage Check works with PHP 8.1.0 or above.


### Submitting bugs and feature requests

Bugs and feature requests are tracked on [GitHub](https://github.com/ericsizemore/phpunit-coverage-check/issues)

Issues are the quickest way to report a bug. If you find a bug or documentation error, please check the following first:

* That there is not an Issue already open concerning the bug
* That the issue has not already been addressed (within closed Issues, for example)


### Contributing

PHPUnit Coverage Check accepts contributions of code and documentation from the community. 
These contributions can be made in the form of Issues or [Pull Requests](http://help.github.com/send-pull-requests/) on the [PHPUnit Coverage Check repository](https://github.com/ericsizemore/phpunit-coverage-check).

PHPUnit Coverage Check is licensed under the MIT license. When submitting new features or patches to PHPUnit Coverage Check, you are giving permission to license those features or patches under the MIT license.

PHPUnit Coverage Check tries to adhere to PHPStan level 9 with strict rules and bleeding edge. Please ensure any contributions do as well.


#### Guidelines

Before we look into how, here are the guidelines. If your Pull Requests fail to pass these guidelines it will be declined, and you will need to re-submit when you’ve made the changes. This might sound a bit tough, but it is required for me to maintain quality of the code-base.


#### PHP Style

Please ensure all new contributions match the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style guide. The project is not fully PSR-12 compatible, yet; however, to ensure the easiest transition to the coding guidelines, I would like to go ahead and request that any contributions follow them.


#### Documentation

If you change anything that requires a change to documentation then you will need to add it. New methods, parameters, changing default values, adding constants, etc. are all things that will require a change to documentation. The change-log must also be updated for every change. Also, PHPDoc blocks must be maintained.


##### Documenting functions/variables (PHPDoc)

Please ensure all new contributions adhere to:

* [PSR-5 PHPDoc](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md)
* [PSR-19 PHPDoc Tags](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc-tags.md)

when documenting new functions, or changing existing documentation.


#### Branching

One thing at a time: A pull request should only contain one change. That does not mean only one commit, but one change - however many commits it took. The reason for this is that if you change X and Y but send a pull request for both at the same time, we might really want X but disagree with Y, meaning we cannot merge the request. Using the Git-Flow branching model you can create new branches for both of these features and send two requests.


### Backward Compatibility Promise

* See [backward-compatibility.md](backward-compatibility.md)


### Author

Eric Sizemore - <admin@secondversion.com> - <https://www.secondversion.com>

### License

PHPUnit Coverage Check is licensed under the MIT [License](LICENSE).
