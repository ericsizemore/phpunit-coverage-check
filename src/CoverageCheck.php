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
use function sprintf;

/**
 * @see Command\CoverageCheckCommand
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
            return sprintf(
                '[ERROR] Total code coverage is %s which is below the accepted %d%%',
                Utils::formatCoverage($results),
                $threshold
            );
        }

        if ($onlyPercentage) {
            return Utils::formatCoverage($results);
        }

        return sprintf('[OK] Total code coverage is %s', Utils::formatCoverage($results));
    }

    /**
     * Parses the clover xml file for coverage metrics.
     *
     * Inspired by: https://ocramius.github.io/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
     * Calculation: https://confluence.atlassian.com/pages/viewpage.action?pageId=79986990
     *
     * @see self::loadMetrics()
     */
    public function process(): float | false
    {
        $metrics = $this->loadMetrics();

        // Ignoring coverage here as theoretically this should not happen
        //@codeCoverageIgnoreStart
        if ($metrics === null || $metrics === false) {
            return false;
        }
        //@codeCoverageIgnoreEnd

        /**
         * According to Atlassian:
         *     TPC = (coveredconditionals + coveredstatements + coveredmethods) / (conditionals + statements + methods)
         *
         * Though it appears elements + coveredelements should work the same, I am reverting back to Atlassian's
         * calculation.
         */

        $metrics = (array) $metrics[0];
        $metrics = array_map('intval', $metrics['@attributes']);

        $coveredMetrics = $metrics['coveredconditionals'] + $metrics['coveredstatements'] + $metrics['coveredmethods'];
        $totalMetrics   = $metrics['conditionals'] + $metrics['statements'] + $metrics['methods'];

        unset($metrics);

        if ($coveredMetrics === 0) {
            return false;
        }

        return $coveredMetrics / $totalMetrics * 100;
    }

    /**
     * Parses the clover xml file for coverage metrics by file.
     *
     * @see self::loadMetrics()
     * @since 2.0.0
     *
     * @return false|array{
     *     fileMetrics: array<string, array{coveredMetrics: int, totalMetrics: int, percentage: int}>,
     *     totalCoverage: float|int
     * }
     *
     * @todo Could possibly clean this up a bit.
     */
    public function processByFile(): false | array
    {
        $fileMetrics   = [];
        $totalCoverage = 0;

        $metrics = $this->loadMetrics(self::XPATH_FILES);

        // Ignoring coverage here as theoretically this should not happen
        //@codeCoverageIgnoreStart
        if ($metrics === null || $metrics === false) {
            return false;
        }
        //@codeCoverageIgnoreEnd

        /**
         * According to Atlassian:
         *     TPC = (coveredconditionals + coveredstatements + coveredmethods) / (conditionals + statements + methods)
         *
         * Though it appears elements + coveredelements should work the same, I am reverting back to Atlassian's
         * calculation.
         */

        foreach ($metrics as $file) {
            $coveredMetrics = (int) ($file->metrics['coveredconditionals'] + $file->metrics['coveredstatements'] + $file->metrics['coveredmethods']);
            $totalMetrics   = (int) ($file->metrics['conditionals'] + $file->metrics['statements'] + $file->metrics['methods']);

            if ($coveredMetrics === 0) {
                continue;
            }

            $fileMetrics[(string) $file['name']] = [
                'coveredMetrics' => $coveredMetrics,
                'totalMetrics'   => $totalMetrics,
                'percentage'     => $coveredMetrics / $totalMetrics * 100,
            ];

            $totalCoverage += $fileMetrics[(string) $file['name']]['percentage'];
        }

        if ($totalCoverage !== 0) {
            $totalCoverage /= \count($fileMetrics);
        }

        if (\count($fileMetrics) < 1) {
            return false;
        }

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
            throw new InvalidArgumentException(sprintf('Invalid input file provided. Was given: %s', $cloverFile));
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
            throw new InvalidArgumentException(sprintf('The threshold must be a minimum of 1 and a maximum of 100, %d given', $threshold));
        }

        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Loads the clover xml data and runs XML Xpath query with self::XPATH_METRICS.
     *
     * @see https://www.php.net/SimpleXMLElement
     *
     * @internal
     *
     * @return array<SimpleXMLElement> | false | null
     *
     * @throws RuntimeException If file_get_contents fails or if XML data cannot be parsed, or
     *                          if the given file does not appear to be a valid clover file.
     */
    protected function loadMetrics(string $xpath = self::XPATH_METRICS): array | false | null
    {
        $cloverData = file_get_contents($this->cloverFile);

        //@codeCoverageIgnoreStart
        if ($cloverData === false || $cloverData === '') {
            throw new RuntimeException(sprintf('Failed to get the contents of %s', $this->cloverFile));
        }
        //@codeCoverageIgnoreEnd

        $xml = Utils::parseXml($cloverData);

        if (!Utils::isPossiblyClover($xml)) {
            throw new RuntimeException('Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?');
        }

        return $xml->xpath($xpath);
    }
}
