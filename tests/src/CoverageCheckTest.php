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

namespace Esi\CoverageCheck\Tests;

use Esi\CoverageCheck\CoverageCheck;
use Esi\CoverageCheck\Utils;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * @internal
 */
#[CoversClass(CoverageCheck::class)]
#[CoversClass(Utils::class)]
class CoverageCheckTest extends TestCase
{
    private CoverageCheck $coverageCheck;

    private static array $fixtures;

    protected function setUp(): void
    {
        $this->coverageCheck = new CoverageCheck();
        self::$fixtures      = [
            'valid'        => dirname(__FILE__, 2) . '/fixtures/clover.xml',
            'notexist'     => dirname(__FILE__, 2) . '/fixtures/clovr.xml',
            'empty'        => dirname(__FILE__, 2) . '/fixtures/empty.xml',
            'invalid_root' => dirname(__FILE__, 2) . '/fixtures/invalid_root_element.xml',
            'no_children'  => dirname(__FILE__, 2) . '/fixtures/no_children.xml',
            'no_metrics'   => dirname(__FILE__, 2) . '/fixtures/no_project_metrics.xml',
        ];
    }

    public function testGetSetCloverFile(): void
    {
        $this->coverageCheck->setCloverFile(self::$fixtures['valid']);
        self::assertSame(self::$fixtures['valid'], $this->coverageCheck->getCloverFile());
    }

    public function testGetSetOnlyPercentage(): void
    {
        $this->coverageCheck->setOnlyPercentage(true);
        self::assertTrue($this->coverageCheck->getOnlyPercentage());

        $this->coverageCheck->setOnlyPercentage(false);
        self::assertFalse($this->coverageCheck->getOnlyPercentage());
    }

    public function testGetSetThreshold(): void
    {
        $this->coverageCheck->setThreshold(100);
        self::assertSame(100, $this->coverageCheck->getThreshold());
    }

    public function testGetSetThresholdInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->coverageCheck->setThreshold(101);
    }

    public function testNonConsoleCallInvalid(): void
    {
        $results = $this->coverageCheck->nonConsoleCall(self::$fixtures['valid'], 100);
        self::assertSame('[ERROR] Total code coverage is 90.32% which is below the accepted 100%', $results);
    }

    public function testNonConsoleCallInvalidOnlyPercentage(): void
    {
        $results = $this->coverageCheck->nonConsoleCall(self::$fixtures['valid'], 100, true);
        self::assertSame('90.32%', $results);
    }

    public function testNonConsoleCallValid(): void
    {
        $results = $this->coverageCheck->nonConsoleCall(self::$fixtures['valid'], 90);
        self::assertSame('[OK] Total code coverage is 90.32%', $results);
    }

    public function testNonConsoleCallValidOnlyPercentage(): void
    {
        $results = $this->coverageCheck->nonConsoleCall(self::$fixtures['valid'], 90, true);
        self::assertSame('90.32%', $results);
    }

    public function testNonConsoleInvalidCloverNoChildren(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->coverageCheck->nonConsoleCall(self::$fixtures['no_children'], 90);
    }

    public function testNonConsoleInvalidCloverNoProjectMetrics(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->coverageCheck->nonConsoleCall(self::$fixtures['no_metrics'], 90);
    }

    public function testNonConsoleInvalidCloverNoRootElement(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->coverageCheck->nonConsoleCall(self::$fixtures['invalid_root'], 90);
    }

    public function testNonConsoleNotEnoughCode(): void
    {
        $results = $this->coverageCheck->nonConsoleCall(self::$fixtures['empty'], 90);
        self::assertSame('[ERROR] Insufficient data for calculation. Please add more code.', $results);
    }

    public function testSetCloverFileThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->coverageCheck->setCloverFile(self::$fixtures['notexist']);
    }
}
