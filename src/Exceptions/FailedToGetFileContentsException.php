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

use RuntimeException;

/**
 * @codeCoverageIgnore
 */
final class FailedToGetFileContentsException extends RuntimeException
{
    public static function create(string $cloverFile): self
    {
        return new self(\sprintf('Failed to get the contents of %s', $cloverFile));
    }
}
