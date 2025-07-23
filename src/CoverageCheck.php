<?php

declare(strict_types=1);

/**
 * This file is part of PHPUnit Coverage Check.
 *
 * (c) Eric Sizemore <admin@secondversion.com>
 * (c) Richard Regeer <rich2309@gmail.com>
 *
 * This source file is subject to the MIT license. For the full copyright,
 * license information, and credits/acknowledgements, please view the LICENSE
 * and README files that were distributed with this source code.
 */

namespace Esi\CoverageCheck;

use Esi\CoverageCheck\Exceptions\FailedToGetFileContentsException;
use Esi\CoverageCheck\Exceptions\InvalidInputFileException;
use Esi\CoverageCheck\Exceptions\NotAValidCloverFileException;
use Esi\CoverageCheck\Exceptions\ThresholdOutOfBoundsException;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;

use function array_map;
use function file_get_contents;

/**
 * @see Command\CoverageCheckCommand
 * @see Tests\CoverageCheckTest
 */
final class CoverageCheck
{
    /**
     * Message returned if coverage falls below a given threshold.
     *
     * Mirrored in \Esi\CoverageCheck\Command\CoverageCheckCommand::ERROR_COVERAGE_BELOW_THRESHOLD without prefix.
     *
     * @see Command\CoverageCheckCommand::ERROR_COVERAGE_BELOW_THRESHOLD
     * @since 3.0.0
     */
    public const string ERROR_COVERAGE_BELOW_THRESHOLD = '[ERROR] Total code coverage is %s%% which is below the accepted %d%%';

    /**
     * Message returned if there is not enough data to calculate coverage.
     *
     * Mirrored in \Esi\CoverageCheck\Command\CoverageCheckCommand::ERROR_INSUFFICIENT_DATA without prefix.
     *
     * @see Command\CoverageCheckCommand::ERROR_INSUFFICIENT_DATA
     * @since 3.0.0
     */
    public const string ERROR_INSUFFICIENT_DATA = '[ERROR] Insufficient data for calculation. Please add more code.';

    /**
     * Message returned if coverage meets or exceeds a given threshold.
     *
     * Mirrored in \Esi\CoverageCheck\Command\CoverageCheckCommand::OK_TOTAL_CODE_COVERAGE without prefix.
     *
     * @see Command\CoverageCheckCommand::OK_TOTAL_CODE_COVERAGE
     * @since 3.0.0
     */
    public const string OK_TOTAL_CODE_COVERAGE = '[OK] Total code coverage is %s%%';

    /**
     * Xpath expression for getting each files' data in a clover report.
     *
     * @since 2.0.0
     */
    protected const string XPATH_FILES = '//file';

    /**
     * Xpath expression for getting the project total metrics in a clover report.
     */
    protected const string XPATH_METRICS = '//project/metrics';

    /**
     * Configurable options.
     *
     * @see self::setCloverFile()
     * @see self::setThreshold()
     * @see self::setOnlyPercentage()
     */
    private string $cloverFile = 'clover.xml';

    private bool $onlyPercentage = false;

    private int $threshold = 100;

    /**
     * Simple getters.
     */

    public function getCloverFile(): string
    {
        return $this->cloverFile;
    }

    public function getOnlyPercentage(): bool
    {
        return $this->onlyPercentage;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    /**
     * Processes the coverage data with the given clover file and threshold, and returns the information
     * similar to how the Console application will.
     *
     * This is mainly useful for those that may wish to use this library outside the CLI/Console or PHAR.
     *
     * @throws InvalidArgumentException If the clover file does not exist, or the threshold is not within
     *                                  defined range (>= 1 <= 100).
     */
    public function nonConsoleCall(string $cloverFile, int $threshold = 100, bool $onlyPercentage = false): string
    {
        $this->setCloverFile($cloverFile)
            ->setThreshold($threshold)
            ->setOnlyPercentage($onlyPercentage);

        $results = $this->process();

        if ($results === false) {
            return self::ERROR_INSUFFICIENT_DATA;
        }

        if ($results < $threshold && !$onlyPercentage) {
            return \sprintf(
                self::ERROR_COVERAGE_BELOW_THRESHOLD,
                Utils::formatCoverage($results),
                $threshold
            );
        }

        if ($onlyPercentage) {
            return Utils::formatCoverage($results);
        }

        return \sprintf(self::OK_TOTAL_CODE_COVERAGE, Utils::formatCoverage($results));
    }

    /**
     * Parses the clover xml file for coverage metrics.
     *
     * According to Atlassian:
     *     TPC = (coveredconditionals + coveredstatements + coveredmethods) / (conditionals + statements + methods)
     *
     * Though it appears elements + coveredelements should work the same, I am sticking with Atlassian's
     * calculation.
     *
     * @see self::loadMetrics()
     * @see https://confluence.atlassian.com/pages/viewpage.action?pageId=79986990
     * @see https://ocramius.github.io/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
     */
    public function process(): false|float
    {
        $rawMetrics = $this->loadMetrics() ?? false;

        // Ignoring coverage here as theoretically this should not happen
        //@codeCoverageIgnoreStart
        if ($rawMetrics === false) {
            return false;
        }

        //@codeCoverageIgnoreEnd

        /**
         * @var array<string> $metrics
         */
        $metrics = ((array) $rawMetrics[0])['@attributes'];

        $metrics = array_map(static fn (string $value): int => \intval($value), $metrics);

        unset($rawMetrics);

        $coveredMetrics = $metrics['coveredconditionals'] + $metrics['coveredstatements'] + $metrics['coveredmethods'];
        $totalMetrics   = $metrics['conditionals'] + $metrics['statements'] + $metrics['methods'];

        unset($metrics);

        if ($totalMetrics === 0) {
            return false;
        }

        return (float) ($coveredMetrics / $totalMetrics) * 100.0;
    }

    /**
     * Parses the clover xml file for coverage metrics by file.
     *
     * @see self::process()
     * @see self::loadMetrics()
     * @see https://confluence.atlassian.com/pages/viewpage.action?pageId=79986990
     * @see https://ocramius.github.io/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
     * @since 2.0.0
     *
     * @return false|array{
     *     fileMetrics: array<string, array{coveredMetrics: int, totalMetrics: int, percentage: float|int}>,
     *     totalCoverage: float|int
     * }
     */
    public function processByFile(): array|false
    {
        $fileMetrics          = [];
        $totalElementsCovered = 0;
        $totalElements        = 0;

        $rawMetrics = $this->loadMetrics(self::XPATH_FILES) ?? false;

        // Ignoring coverage here as theoretically this should not happen
        //@codeCoverageIgnoreStart
        if ($rawMetrics === false) {
            return false;
        }

        //@codeCoverageIgnoreEnd

        foreach ($rawMetrics as $rawMetric) {
            /**
             * @var array<string> $metrics
             */
            $metrics = ((array) $rawMetric->metrics)['@attributes'];

            $metrics = array_map(static fn (string $value): int => \intval($value), $metrics);

            $coveredMetrics = ($metrics['coveredconditionals'] + $metrics['coveredstatements'] + $metrics['coveredmethods']);
            $totalMetrics   = ($metrics['conditionals'] + $metrics['statements'] + $metrics['methods']);

            if ($totalMetrics === 0) {
                continue;
            }

            $coveragePercentage = (float) ($coveredMetrics / $totalMetrics) * 100.0;
            $totalElementsCovered += $coveredMetrics;
            $totalElements        += $totalMetrics;

            $fileMetrics[(string) $rawMetric['name']] = [
                'coveredMetrics' => $coveredMetrics,
                'totalMetrics'   => $totalMetrics,
                'percentage'     => $coveragePercentage,
            ];
        }

        unset($rawMetrics);

        if ($totalElements === 0) {
            return false;
        }

        $totalCoverage = (float) ($totalElementsCovered / $totalElements) * 100.0;

        return [
            'fileMetrics'   => $fileMetrics,
            'totalCoverage' => $totalCoverage,
        ];
    }

    /**
     * Simple setters.
     */

    /**
     * @throws InvalidInputFileException If the given file is empty or does not exist.
     */
    public function setCloverFile(string $cloverFile): CoverageCheck
    {
        if (!Utils::validateCloverFile($cloverFile)) {
            throw InvalidInputFileException::create($cloverFile);
        }

        $this->cloverFile = $cloverFile;

        return $this;
    }

    public function setOnlyPercentage(bool $enable = false): CoverageCheck
    {
        $this->onlyPercentage = $enable;

        return $this;
    }

    /**
     * @throws ThresholdOutOfBoundsException If the threshold is less than 1 or greater than 100.
     */
    public function setThreshold(int $threshold): CoverageCheck
    {
        if (!Utils::validateThreshold($threshold)) {
            throw ThresholdOutOfBoundsException::create($threshold);
        }

        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Loads the clover xml data and runs an XML Xpath query.
     *
     * @internal
     *
     * @param self::XPATH_* $xpath
     *
     * @throws RuntimeException If file_get_contents fails or if XML data cannot be parsed, or
     *                          if the given file does not appear to be a valid clover file.
     *
     * @return null|array<SimpleXMLElement>|false
     */
    private function loadMetrics(string $xpath = self::XPATH_METRICS): null|array|false
    {
        $cloverData = file_get_contents($this->cloverFile);

        //@codeCoverageIgnoreStart
        if ($cloverData === false || $cloverData === '') {
            throw FailedToGetFileContentsException::create($this->cloverFile);
        }

        //@codeCoverageIgnoreEnd

        $xml = Utils::parseXml($cloverData);

        if (!Utils::isPossiblyClover($xml)) {
            throw NotAValidCloverFileException::create();
        }

        return $xml->xpath($xpath);
    }
}
