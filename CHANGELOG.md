# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [Unreleased]

### Added

  * A new `Esi\CoverageCheck\Application` class which extends `Symfony\Component\Console\Application`.
    * Overrides `getDefaultInputDefinition()` and `configureIO()` to cleanup help output.
  * New `Utils` class.
    * Adds a new function `isPossiblyClover()`, which attempts to ensure the provided file is a PHPUnit generated clover file.
      * If a file does not appear to be a valid clover file, it will return the following message:
        * [ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?
    * Handles checking file existence for the clover file.
    * Handles checking if threshold is within accepted range.
  * New `Style\CoverageCheckStyle` which extends `Symfony\Console\Style\SymfonyStyle` to format console output.
  * New shortcut for the `--only-percentage` option for the Console. You can use `-O` instead.

### Changed

  * Changed `coverage-check` to use the `coverage:check` command by default, so you no longer have to call the command to start a check.
    * For example, before:
```bash
php vendor/bin/coverage-check coverage:check /path/to/clover.xml 90
```
    * After:
```bash
php vendor/bin/coverage-check /path/to/clover.xml 90
```
  * Refactored `CoverageCheckCommand::execute`, and a bit of cleanup.
  * Refactored `CoverageCheck::process`. Since we are using `//project/metrics` instead of `//metrics` in `xpath()`, we only need to use `elements` and `coveredelements` for totals.
  * Refactored `CoverageCheck::loadMetrics`. It will also now throw an `Exception` if `file_get_contents` fails for whatever reason or a `RuntimeException` if the new `isPossiblyClover` returns false.
  * Class const `XPATH_METRICS` now has `protected` visibility.
  * Use `SymfonyStyle` via our custom `Style\CoverageCheckStyle` class to handle output instead of `writeln` and the `formatter` helper.
  * Changed output message formats for `CoverageCheck::nonConsoleCall` and the Console to match more closely:
    * Old messages:
      * Insufficient data for calculation. Please add more code.
      * Total code coverage is %s which is below the accepted %d%%
      * Total code coverage is %s - OK!
    * New messages:
      * [ERROR] Insufficient data for calculation. Please add more code.
      * [ERROR] Total code coverage is %s which is below the accepted %d%%
      * [OK] Total code coverage is %s


## [1.0.0] - 2024-03-26

This intial version is forked from [rregeer/phpunit-coverage-check](https://github.com/richardregeer/phpunit-coverage-check/) by [Richard Regeer](https://github.com/richardregeer). This is the CHANGELOG for changes in comparison to the original library.

### Added

  * Created `backward-compatibility.md` for the Backward Compatibility Promise.
  * Created `CHANGELOG.md` to keep track of changes.
  * Created `SECURITY.md` for the security policy.
  * Created testing and analysis workflows based on GitHub actions.
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


[1.0.0]: https://github.com/ericsizemore/phpunit-coverage-check/releases/tag/1.0.0