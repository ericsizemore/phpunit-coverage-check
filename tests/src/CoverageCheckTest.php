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

namespace Esi\CoverageCheck\Tests;

use Esi\CoverageCheck\CoverageCheck;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * @internal
 */
#[CoversClass(CoverageCheck::class)]
class CoverageCheckTest extends TestCase
{
    private CoverageCheck $check;
    private static $fixtures;

    protected function setUp(): void
    {
        $this->check    = new CoverageCheck();
        self::$fixtures = [
            'valid'   => dirname(__FILE__, 2) . '/fixtures/clover.xml',
            'invalid' => dirname(__FILE__, 2) . '/fixtures/clovr.xml',
            'empty'   => dirname(__FILE__, 2) . '/fixtures/empty.xml',
        ];
    }

    public function testGetSetCloverFile(): void
    {
        $this->check->setCloverFile(self::$fixtures['valid']);
        self::assertSame(self::$fixtures['valid'], $this->check->getCloverFile());
    }

    public function testGetSetOnlyPercentage(): void
    {
        $this->check->setOnlyPercentage(true);
        self::assertTrue($this->check->getOnlyPercentage());

        $this->check->setOnlyPercentage(false);
        self::assertFalse($this->check->getOnlyPercentage());
    }

    public function testGetSetThreshold(): void
    {
        $this->check->setThreshold(100);
        self::assertSame(100, $this->check->getThreshold());
    }

    public function testGetSetThresholdInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->check->setThreshold(101);
    }

    public function testNonConsoleCallInvalid(): void
    {
        $results = $this->check->nonConsoleCall(self::$fixtures['valid'], 100);
        self::assertSame('Total code coverage is 90.32 % which is below the accepted 100 %', $results);
    }

    public function testNonConsoleCallInvalidOnlyPercentage(): void
    {
        $results = $this->check->nonConsoleCall(self::$fixtures['valid'], 100, true);
        self::assertSame('90.32 %', $results);
    }

    public function testNonConsoleCallValid(): void
    {
        $results = $this->check->nonConsoleCall(self::$fixtures['valid'], 90);
        self::assertSame('Total code coverage is 90.32 % - OK!', $results);
    }

    public function testNonConsoleCallValidOnlyPercentage(): void
    {
        $results = $this->check->nonConsoleCall(self::$fixtures['valid'], 90, true);
        self::assertSame('90.32 %', $results);
    }

    public function testNonConsoleNotEnoughCode(): void
    {
        $results = $this->check->nonConsoleCall(self::$fixtures['empty'], 90);
        self::assertSame('Insufficient data for calculation. Please add more code.', $results);
    }

    public function testSetCloverFileInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->check->setCloverFile(self::$fixtures['invalid']);
    }
}
