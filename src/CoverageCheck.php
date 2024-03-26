<?php

declare(strict_types=1);

/**
 * This file is part of PHPUnit Coverage Check.
 *
 * (c) Eric Sizemore <admin@secondversion.com>
 * (c) Richard Regeer <rich2309@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Esi\CoverageCheck;

use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

use function file_exists;
use function file_get_contents;
use function sprintf;

/**
 * @see \Esi\CoverageCheck\Command\CoverageCheckCommand
 */
class CoverageCheck
{
    /**
     * Current library version.
     */
    public const VERSION = '1.0.0';

    /**
     * Xpath expression for finding the metrics in a clover file.
     */
    public const XPATH_METRICS = '//project/metrics';

    /**
     * Configurable options.
     *
     * @see self::setCloverFile()
     * @see self::setThreshold()
     * @see self::setOnlyPercentage()
     */
    protected string $cloverFile   = 'clover.xml';
    protected bool $onlyPercentage = false;
    protected int $threshold       = 100;

    /**
     * Constructor. Doesn't need to do anything, at least for the moment.
     */
    public function __construct() {}

    /**
     * Returns the given number formatted and rounded for percentage.
     */
    public static function formatCoverage(float $number): string
    {
        return sprintf('%0.2f %%', $number);
    }

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
     * This is mainly useful for those that may wish to use this library outside of the CLI/Console or PHAR.
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
            return 'Insufficient data for calculation. Please add more code.';
        }

        if ($results < $threshold && !$onlyPercentage) {
            return sprintf(
                'Total code coverage is %s which is below the accepted %d %%',
                self::formatCoverage($results),
                $threshold
            );
        }

        if ($results < $threshold && $onlyPercentage) {
            return self::formatCoverage($results);
        }

        if ($onlyPercentage) {
            return self::formatCoverage($results);
        }

        return sprintf('Total code coverage is %s - OK!', self::formatCoverage($results));
    }

    /**
     * Parses the clover xml file for coverage metrics.
     *
     * Inspired by: https://ocramius.github.io/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
     * Calculation: https://confluence.atlassian.com/pages/viewpage.action?pageId=79986990
     *
     * @see self::loadMetrics()
     *
     * @throws Exception
     */
    public function process(): float | false
    {
        $conditionals        = 0;
        $coveredConditionals = 0;
        $statements          = 0;
        $coveredStatements   = 0;
        $methods             = 0;
        $coveredMethods      = 0;

        foreach ($this->loadMetrics() as $metric) {
            $conditionals        += (int) $metric['conditionals'];
            $coveredConditionals += (int) $metric['coveredconditionals'];
            $statements          += (int) $metric['statements'];
            $coveredStatements   += (int) $metric['coveredstatements'];
            $methods             += (int) $metric['methods'];
            $coveredMethods      += (int) $metric['coveredmethods'];
        }

        $coveredMetrics = $coveredStatements + $coveredMethods + $coveredConditionals;
        $totalMetrics   = $statements + $methods + $conditionals;

        if ($totalMetrics === 0) {
            return false;
        }

        return $coveredMetrics / $totalMetrics * 100;
    }

    /**
     * @throws InvalidArgumentException If the given file is empty or does not exist.
     */
    public function setCloverFile(string $cloverFile): CoverageCheck
    {
        if ($cloverFile === '' || !file_exists($cloverFile)) {
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
        if ($threshold < 1 || $threshold > 100) {
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
     * @return array<SimpleXMLElement>
     *
     * @throws Exception If XML data cannot be parsed.
     */
    protected function loadMetrics(): array
    {
        $xml = new SimpleXMLElement((string) file_get_contents($this->cloverFile));

        return $xml->xpath(self::XPATH_METRICS);
    }
}
