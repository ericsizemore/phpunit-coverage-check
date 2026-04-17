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

namespace Esi\CoverageCheck\Tests\Data;

use Esi\CoverageCheck\Data\Threshold;
use Esi\CoverageCheck\Exceptions\InvalidThresholdException;
use Esi\CoverageCheck\Exceptions\ThresholdOutOfBoundsException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Threshold::class)]
#[UsesClass(InvalidThresholdException::class)]
#[UsesClass(ThresholdOutOfBoundsException::class)]
final class ThresholdTest extends TestCase
{
    public function testFromStringValidInteger(): void
    {
        $threshold = Threshold::fromString('100');
        self::assertSame(100.0, $threshold->value);
    }

    public function testFromStringValidFloat(): void
    {
        $threshold = Threshold::fromString('95.5');
        self::assertSame(95.5, $threshold->value);
    }

    public function testFromStringValidSmallValue(): void
    {
        $threshold = Threshold::fromString('0.01');
        self::assertSame(0.01, $threshold->value);
    }

    public function testFromStringInvalidNonNumeric(): void
    {
        $this->expectException(InvalidThresholdException::class);
        $this->expectExceptionMessage('Invalid threshold provided. Was given: abc, but should be numeric.');
        Threshold::fromString('abc');
    }

    public function testFromStringInvalidEmpty(): void
    {
        $this->expectException(InvalidThresholdException::class);
        Threshold::fromString('');
    }

    public function testFromStringInvalidTooHigh(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 101 given');
        Threshold::fromString('101');
    }

    public function testFromStringInvalidTooHighFloat(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        Threshold::fromString('100.01');
    }

    public function testFromStringInvalidTooLow(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 0 given');
        Threshold::fromString('0');
    }

    public function testFromStringInvalidNegative(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        Threshold::fromString('-5');
    }

    public function testFromIntValid(): void
    {
        $threshold = Threshold::fromInt(50);
        self::assertSame(50.0, $threshold->value);
    }

    public function testFromIntInvalidTooHigh(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        Threshold::fromInt(101);
    }

    public function testFromIntInvalidTooLow(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        Threshold::fromInt(0);
    }

    public function testFromFloatValid(): void
    {
        $threshold = Threshold::fromFloat(75.5);
        self::assertSame(75.5, $threshold->value);
    }

    public function testFromFloatValidBoundaryMin(): void
    {
        $threshold = Threshold::fromFloat(0.01);
        self::assertSame(0.01, $threshold->value);
    }

    public function testFromFloatValidBoundaryMax(): void
    {
        $threshold = Threshold::fromFloat(100.0);
        self::assertSame(100.0, $threshold->value);
    }

    public function testFromFloatInvalidTooHigh(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        Threshold::fromFloat(100.01);
    }

    public function testFromFloatInvalidTooLow(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        Threshold::fromFloat(0.0);
    }

    public function testFromFloatInvalidNegative(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        Threshold::fromFloat(-10.5);
    }

    #[DataProvider('validFromInputProvider')]
    public function testFromValidInput(string|int|float $input, float $expected): void
    {
        $threshold = Threshold::from($input);
        self::assertSame($expected, $threshold->value);
    }

    /**
     * @return array<string, array{string|int|float, float}>
     */
    public static function validFromInputProvider(): array
    {
        return [
            'string integer' => ['50', 50.0],
            'string float' => ['50.5', 50.5],
            'int' => [50, 50.0],
            'float' => [50.5, 50.5],
        ];
    }

    /**
     * @param string|int|float $input
     */
    #[DataProvider('invalidFromInputProvider')]
    public function testFromInvalidInput(string|int|float $input): void
    {
        $this->expectException(\Exception::class);
        Threshold::from($input);
    }

    /**
     * @return array<string, array{string|int|float}>
     */
    public static function invalidFromInputProvider(): array
    {
        return [
            'string non-numeric' => ['abc'],
            'string too high' => ['101'],
            'string too low' => ['0'],
            'int too high' => [101],
            'int too low' => [0],
            'float too high' => [100.01],
            'float too low' => [0.0],
        ];
    }

    public function testFormatted(): void
    {
        $threshold = Threshold::fromFloat(90.123);
        self::assertSame('90.12%', $threshold->formatted());
    }

    public function testFormattedRounding(): void
    {
        $threshold = Threshold::fromFloat(90.999);
        self::assertSame('91.00%', $threshold->formatted());
    }

    public function testFormattedInteger(): void
    {
        $threshold = Threshold::fromInt(100);
        self::assertSame('100.00%', $threshold->formatted());
    }

    public function testToString(): void
    {
        $threshold = Threshold::fromFloat(85.5);
        self::assertSame('85.50%', (string) $threshold);
    }

    public function testToStringEqualsFormatted(): void
    {
        $threshold = Threshold::fromFloat(75.123);
        self::assertSame($threshold->formatted(), (string) $threshold);
    }

    public function testIsReadonly(): void
    {
        $threshold = Threshold::fromFloat(50.0);
        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $threshold->value = 100.0;
    }
}
