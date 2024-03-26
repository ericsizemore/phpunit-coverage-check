# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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