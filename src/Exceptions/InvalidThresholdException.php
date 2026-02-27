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

namespace Esi\CoverageCheck\Exceptions;

use InvalidArgumentException;

final class InvalidThresholdException extends InvalidArgumentException
{
    public static function create(string $threshold): self
    {
        return new self(\sprintf('Invalid threshold provided. Was given: %s, but should be numeric.', $threshold));
    }
}
