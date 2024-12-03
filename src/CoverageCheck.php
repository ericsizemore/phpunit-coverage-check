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

use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;

use function array_map;
use function file_get_contents;

/**
 * @see Command\CoverageCheckCommand
 * @see Tests\CoverageCheckTest
 */
class CoverageCheck
{
    /**
     * Xpath expression for getting each file's data in a clover report.
     *
     * @since 2.0.0
     */
    protected const XPATH_FILES = '//file';

    /**
     * Xpath expression for getting the project total metrics in a clover report.
     */
    protected const XPATH_METRICS = '//project/metrics';

    /**
     * Configurable options.
     *
     * @see self::setCloverFile()
     * @see self::setThreshold()
     * @see self::setOnlyPercentage()
     */
    protected string $cloverFile = 'clover.xml';

    protected bool $onlyPercentage = false;

    protected int $threshold = 100;

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
            return '[ERROR] Insufficient data for calculation. Please add more code.';
        }

        if ($results < $threshold && !$onlyPercentage) {
            return \sprintf(
                '[ERROR] Total code coverage is %s which is below the accepted %d%%',
                Utils::formatCoverage($results),
                $threshold
            );
        }

        if ($onlyPercentage) {
            return Utils::formatCoverage($results);
        }

        return \sprintf('[OK] Total code coverage is %s', Utils::formatCoverage($results));
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

        return $coveredMetrics / $totalMetrics * 100;
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

        foreach ($rawMetrics as $file) {
            /**
             * @var array<string> $metrics
             */
            $metrics = ((array) $file->metrics)['@attributes'];

            $metrics = array_map(static fn (string $value): int => \intval($value), $metrics);

            $coveredMetrics = ($metrics['coveredconditionals'] + $metrics['coveredstatements'] + $metrics['coveredmethods']);
            $totalMetrics   = ($metrics['conditionals'] + $metrics['statements'] + $metrics['methods']);

            if ($totalMetrics === 0) {
                continue;
            }

            $coveragePercentage = $coveredMetrics / $totalMetrics * 100;
            $totalElementsCovered += $coveredMetrics;
            $totalElements        += $totalMetrics;

            $fileMetrics[(string) $file['name']] = [
                'coveredMetrics' => $coveredMetrics,
                'totalMetrics'   => $totalMetrics,
                'percentage'     => $coveragePercentage,
            ];
        }

        unset($rawMetrics);

        if ($totalElements === 0) {
            return false;
        }

        $totalCoverage = $totalElementsCovered / $totalElements * 100;

        return [
            'fileMetrics'   => $fileMetrics,
            'totalCoverage' => $totalCoverage,
        ];
    }

    /**
     * Simple setters.
     */

    /**
     * @throws InvalidArgumentException If the given file is empty or does not exist.
     */
    public function setCloverFile(string $cloverFile): CoverageCheck
    {
        if (!Utils::validateCloverFile($cloverFile)) {
            throw new InvalidArgumentException(\sprintf('Invalid input file provided. Was given: %s', $cloverFile));
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
     * @throws InvalidArgumentException If the threshold is less than 1 or greater than 100.
     */
    public function setThreshold(int $threshold): CoverageCheck
    {
        if (!Utils::validateThreshold($threshold)) {
            throw new InvalidArgumentException(\sprintf('The threshold must be a minimum of 1 and a maximum of 100, %d given', $threshold));
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
    protected function loadMetrics(string $xpath = self::XPATH_METRICS): null|array|false
    {
        $cloverData = file_get_contents($this->cloverFile);

        //@codeCoverageIgnoreStart
        if ($cloverData === false || $cloverData === '') {
            throw new RuntimeException(\sprintf('Failed to get the contents of %s', $this->cloverFile));
        }
        //@codeCoverageIgnoreEnd

        $xml = Utils::parseXml($cloverData);

        if (!Utils::isPossiblyClover($xml)) {
            throw new RuntimeException('Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?');
        }

        return $xml->xpath($xpath);
    }
}
