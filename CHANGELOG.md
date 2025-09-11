# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [Unreleased]

 ### Added

  * (feat) CLI option for table width
    * Thanks to [LeoVie, discussion #311](https://github.com/ericsizemore/phpunit-coverage-check/discussions/311) for the idea.

### Changed

  * The `composer.json` script `psalm:ci` now uses the `psalm` script instead of the (now removed) `psalm:security`.
  * Updated dev-dependencies, most notably requiring Psalm v7-beta.
  * Updated `Command\CoverageCheckCommand` to use `__invoke()` instead of `configure()` and `execute()`. This was introduced in [Symfony 7.3](https://symfony.com/blog/new-in-symfony-7-3-invokable-commands-and-input-attributes).
    * Note, it still extends `Symfony\Component\Console\Command\Command` for now. 
  * Updated `README.md` for some minor enhancements, and to add information for the new `--table-width` option.
  * Updated `processByFile()` to handle the new experimental support for OpenClover in PHPUnit 12.2+

### Removed

  * The `composer.json` script `psalm:security` has been removed.


## [3.0.0] - 2024-12-16

This release is mainly a bump to a PHP 8.3 requirement with some minor refactoring.

### Added

  * Added [rector/rector](https://github.com/rectorphp/rector) to dev-dependencies.
  * Added new class constants to `Esi\CoverageCheck\CoverageCheck` and `Esi\CoverageCheck\Command\CoverageCheckCommand` for ERROR and OK messages, so they are easier to update (if needed) in the future.
    *  The constants are the same for both classes, with some exceptions:
      * The constants in `CoverageCheckCommand` do not use the '[ERROR]' and '[OK]' prefixes as the `symfony/console` method(s) used to output these messages add the prefixes automatically.
      * The constants in `CoverageCheckCommand` also remove the extra '%' in the format as it is added in with the formatted coverage.
  * Added new Exceptions:
    * `Exceptions\FailedToGetFileContentsException`
    * `Exceptions\InvalidInputFileException`
    * `Exceptions\NotAValidCloverFileException`
    * `Exceptions\ThresholdOutOfBoundsException`
  * Added [vimeo/psalm](https://github.com/vimeo/psalm), [psalm/plugin-phpunit](https://github.com/psalm/psalm-plugin-phpunit), and [psalm/plugin-symfony](https://github.com/psalm/psalm-plugin-symfony) as dev-dependencies
    * Fixes implemented based on Psalm reported errors.

### Changed

  * Minimum PHP version bumped to 8.3.
  * Class constants updated to have the appropriate typing.
  * All classes marked `final`, and the `Utils` class made abstract since it is never instantiated (all static methods).
  * `coverage-check` file moved to `bin/coverage-check`
  * Update to PHPUnit 12 (`^12.0-dev` until official release).
  * Updated `phpstan-baseline.neon` as it doesn't seem to understand `xpath()`'s ability to possibly return `null` or `false`.
  * Add nightly PHP (8.5-dev) to GitHub workflow `continuous-integration.yml`.


## [2.0.2] - 2024-12-03

### Changed

  * Updated total coverage calculation within the CoverageCheck::processByFile() method, as it was producing different totals than the normal process method.
    * Now relies on total elements and total covered elements, instead of gathering percent coverage for each file and dividing by file count.  
    * Updated unit test(s) accordingly


## [2.0.1] - 2024-10-09

### Added

  * Adds `phpstan-baseline.neon` for PHPStan.
  * Adds new GitHub workflow to generate the Phar and uploads an artifact:
    * On release - if it is a release, it also signs the Phar and uploads the `.asc` for verification
    * For pull requests
    * On a schedule
  * Adds `phpstan/extension-installer` with `composer.json` config updated to allow plugins for PHPStan.

### Changed

  * Updated coding standards with PHP-CS-Fixer, applied fixes.
  * Updated `backward-compatibility.md`
  * Updated `CONTRIBUTING.md` with more guidelines/information.
  * Updated `.gitattributes`
  * Updates:
    * `phpstan` to the non-dev `1.12.0`
    * `phpstan/phpstan-phpunit` to the non-dev `1.4.0`
    * `phpstan/phpstan-strict-rules` to the non-dev `1.6.0`
  * The `box` configuration for generating the Phar has been updated.
    * The Phar will now be scoped to `Esi\CoverageCheckPhar`.
    * The Phar now will be gz compressed, which adds `ext-zlib` as a requirement to run it.


## [2.0.0] - 2024-04-21

### Added

  * A new `Esi\CoverageCheck\Application` class which extends `Symfony\Component\Console\Application`.
    * Overrides `getDefaultInputDefinition()` and `configureIO()` to clean up help output.
  * New `Utils` class.
    * Adds a new function `isPossiblyClover()`, which attempts to ensure the provided file is a PHPUnit generated clover file.
  * New `Style\CoverageCheckStyle` which extends `Symfony\Console\Style\SymfonyStyle` to format console output.
  * New shortcut for the `--only-percentage` option for the Console. You can use `-O` instead.
  * New option `--show-files` (shortcut `-F`), to break down metrics by file, and output the results via a table.
    * This adds a new constant `CoverageCheck::XPATH_FILES` and the following functions:
      * `CoverageCheck::processByFile()`
      * `CoverageCheckCommand::getFileTable()`
      * `CoverageCheckCommand::getResultOutput()`

### Changed

  * Minimum PHP version increased to 8.2.
    * Symfony minimum ^7.0
    * PHPUnit minimum ^11.0
  * The use of `coverage:check` when calling the Phar or the bin file is no longer needed.
```bash
# before
$ php vendor/bin/coverage-check coverage:check /path/to/clover.xml 90

# after
$ php vendor/bin/coverage-check /path/to/clover.xml 90
```
  * `CoverageCheck::loadMetrics` can now throw a `RuntimeException` if `file_get_contents` fails for whatever reason or if the new `isPossiblyClover` returns false.
    * With the addition of the new `--show-files` option and related additions, the `CoverageCheck::loadMetrics()` now has one parameter: `$xpath`.
  * Class const `XPATH_METRICS` now has `protected` visibility.
  * Class const `APPLICATION_NAME` and `VERSION` moved from `CoverageCheck` to `Application`.
    * Subsequently, `Application` now also overrides the parent `Symfony\Component\Console\Application` constructor and passes these values to the parent class.
  * Changed output message formats for `CoverageCheck::nonConsoleCall` and the Console to match more closely:
    * Old messages:
      * Insufficient data for calculation. Please add more code.
      * Total code coverage is %s which is below the accepted %d%%
      * Total code coverage is %s - OK!
    * New messages:
      * [ERROR] Insufficient data for calculation. Please add more code.
      * [ERROR] Total code coverage is %s which is below the accepted %d%%
      * [OK] Total code coverage is %s
  * Unit tests updated accordingly.

### Fixed

  * `CoverageCheckCommand` updated to use the `Symfony\Console\Attribute\AsCommand` attribute, as using the static properties is deprecated as of `Symfony\Console` 6.1.

### Removed

  * Removed dev-dependency for `phpstan\phpstan-symfony`. Seemed to be a bit overkill to require a dependency for something I was able to solve with a couple extra lines, and was running into an issue where it seemed to suppress other issues from being reported.
  * Removed `tests/console-application.php`, since it was a requirement for phpstan-symfony.

### TODO

  * The new `--show-files` option is not yet supported in `CoverageCheck::nonConsoleCall()`.
  * Cleanup, and add to, documentation throughout.


## [1.0.0] - 2024-03-26

This initial version is forked from [rregeer/phpunit-coverage-check](https://github.com/richardregeer/phpunit-coverage-check/) by [Richard Regeer](https://github.com/richardregeer). This is the CHANGELOG for changes in comparison to the original library.

### Added

  * `backward-compatibility.md` for the Backward Compatibility Promise.
  * `CHANGELOG.md` to keep track of changes.
  * `SECURITY.md` for the security policy.
  * Testing and analysis workflows based on GitHub actions.
  * Unit testing via PHPUnit.
  * `box.json` that is used for generating Phar files for the library.
    * Phar file will be added to each new release.
  * Dev dependencies for PHPStan (and strict rules, bleeding edge, Symfony plugins) and PHPCS-Fixer.
  * Dependency scanning via Renovate.
  * `Symfony\Console` is now a runtime dependency.
  * Library is now namespace'd to `Esi\CoverageCheck`.

### Changed

  * Reformatted composer.json, added more information/sections.
  * Minimum PHP version bumped to 8.1.
  * `coverage-check` (instead of coverage-check.php) in the root directory, which is the bin file.
  * Updated `README.md` with more information, and changes to the usage of this library.
  * This library is now a `Symfony\Console` application.

### Removed

  * `coverage-check.php`, `bin/coverage-check`, and `test/run` removed.


[unreleased]: https://github.com/ericsizemore/phpunit-coverage-check/tree/master
[3.0.0]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/3.0.0
[2.0.2]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/2.0.2
[2.0.1]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/2.0.1
[2.0.0]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/2.0.0
[1.0.0]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/1.0.0
