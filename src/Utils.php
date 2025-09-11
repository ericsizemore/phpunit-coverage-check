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
use RuntimeException;
use SimpleXMLElement;

use function file_exists;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function trim;

use const LIBXML_ERR_ERROR;
use const LIBXML_ERR_FATAL;
use const LIBXML_ERR_WARNING;
use const PHP_EOL;

abstract class Utils
{
    /**
     * Returns the given number formatted and rounded for percentage.
     */
    public static function formatCoverage(float $number): string
    {
        return \sprintf('%0.2F%%', $number);
    }

    /**
     * Attempts to determine if we are actually using a PHPUnit generated clover file.
     *
     * @todo As of version 12.2, PHPUnit now supports generating OpenClover.
     * @todo Look into validating against the official clover XSD.
     *
     * @see https://bitbucket.org/atlassian/clover/raw/master/etc/schema/clover.xsd
     * @see https://github.com/sebastianbergmann/phpunit/releases/tag/12.2.0
     */
    public static function isPossiblyClover(SimpleXMLElement $xml): bool
    {
        if ($xml->getName() !== 'coverage') {
            return false;
        }

        $hasChildren = $xml->children();

        if ($hasChildren === null || !isset($hasChildren->project, $hasChildren->project->metrics) || (array) $hasChildren->project->metrics === []) {
            return false;
        }

        unset($hasChildren);

        return true;
    }

    /**
     * Handles parsing the XML data returned by CoverageCheck::loadMetrics().
     *
     * Attempts to gather any potential errors returned by SimpleXml/LibXml and wrap them
     * in a RuntimeException.
     *
     * @see https://www.php.net/SimpleXMLElement
     * @see https://www.php.net/libxml_use_internal_errors
     * @see https://www.php.net/libxml_get_errors
     *
     * @throws RuntimeException For any xml parser related errors.
     */
    public static function parseXml(string $xmlData): SimpleXMLElement
    {
        /**
         * @var array<int, string> $errorLevels
         */
        static $errorLevels = [
            LIBXML_ERR_WARNING => 'Warning',
            LIBXML_ERR_ERROR   => 'Error',
            LIBXML_ERR_FATAL   => 'Fatal Error',
        ];

        libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($xmlData);
        } catch (Exception) {
            $errorMessage = PHP_EOL;

            foreach (libxml_get_errors() as $libXMLError) {
                $errorMessage .= \sprintf(
                    '%s %d: %s. Line %d Column %d',
                    $errorLevels[$libXMLError->level],
                    $libXMLError->code,
                    trim($libXMLError->message),
                    $libXMLError->line,
                    $libXMLError->column
                ) . PHP_EOL;
            }

            throw new RuntimeException(\sprintf('Unable to load Clover XML data. LibXml returned: %s', $errorMessage));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(false);
        }

        return $xml;
    }

    /**
     * A simple file_exists check on the Clover file.
     */
    public static function validateCloverFile(string $cloverFile): bool
    {
        return ($cloverFile !== '' && file_exists($cloverFile));
    }

    /**
     * A simple check to determine if threshold is within accepted range (Min. 1, Max. 100).
     */
    public static function validateThreshold(int $threshold): bool
    {
        return ($threshold > 0 && $threshold <= 100);
    }
}
