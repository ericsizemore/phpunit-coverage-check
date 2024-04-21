# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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


[2.0.0]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/2.0.0
[1.0.0]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/1.0.0
