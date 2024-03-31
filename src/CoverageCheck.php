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

use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

use function file_get_contents;
use function sprintf;

/**
 * @see \Esi\CoverageCheck\Command\CoverageCheckCommand
 */
class CoverageCheck
{
    /**
     * Application / library name. (used in the Console Application).
     */
    public const APPLICATION_NAME = 'PHPUnit Coverage Check';

    /**
     * Current library version. (used in the Console Application).
     */
    public const VERSION = '1.1.0';

    /**
     * Xpath expression for finding the metrics in a clover file.
     */
    protected const XPATH_METRICS = '//project/metrics';

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
     *
     * @throws Exception
     */
    public function process(): float | false
    {
        $metrics = (array) $this->loadMetrics()[0];
        $metrics = \array_map('intval', $metrics['@attributes']);

        if ($metrics['elements'] === 0) {
            return false;
        }

        return $metrics['coveredelements'] / $metrics['elements'] * 100;
    }

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
     * @codeCoverageIgnore
     *
     * @return array<SimpleXMLElement>
     *
     * @throws Exception If file_get_contents fails or if XML data cannot be parsed.
     */
    protected function loadMetrics(): array
    {
        $cloverData = file_get_contents($this->cloverFile);

        if ($cloverData === false || $cloverData === '') {
            throw new Exception(sprintf('Failed to get the contents of %s', $this->cloverFile));
        }

        $xml = new SimpleXMLElement($cloverData);

        if (!Utils::isPossiblyClover($xml)) {
            throw new \RuntimeException('Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?');
        }

        return $xml->xpath(self::XPATH_METRICS);
    }
}
