<?php

declare(strict_types=1);

namespace Esi\CoverageCheck\Data;

use Esi\CoverageCheck\Exceptions\InvalidThresholdException;
use Esi\CoverageCheck\Exceptions\ThresholdOutOfBoundsException;

final readonly class Threshold
{
    public function __construct(
        public float $value,
    ) {
    }

    public static function from(string|int|float $threshold): self
    {
        return match (\gettype($threshold)) {
            'string' => self::fromString($threshold),
            'integer' => self::fromInt($threshold),
            'double' => self::fromFloat($threshold),
        };
    }

    public static function fromString(string $threshold): self
    {
        if (!\is_numeric($threshold)) {
            throw InvalidThresholdException::create($threshold);
        }

        $floatValue = (float) $threshold;

        self::validate($floatValue);

        return new self($floatValue);
    }

    public static function fromInt(int $threshold): self
    {
        return self::fromFloat((float) $threshold);
    }

    public static function fromFloat(float $threshold): self
    {
        self::validate($threshold);

        return new self($threshold);
    }

    private static function validate(float $threshold): void
    {
        if ($threshold <= 0 || $threshold > 100) {
            throw ThresholdOutOfBoundsException::create($threshold);
        }
    }

    public function formatted(): string
    {
        return \sprintf('%0.2F%%', $this->value);
    }

    public function __toString(): string
    {
        return $this->formatted();
    }
}
