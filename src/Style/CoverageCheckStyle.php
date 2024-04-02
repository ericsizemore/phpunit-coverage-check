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

namespace Esi\CoverageCheck\Style;

use Symfony\Component\Console\Style\SymfonyStyle;

class CoverageCheckStyle extends SymfonyStyle
{
    /**
     * @see SymfonyStyle
     *
     * @inheritDoc
     * @phpstan-ignore missingType.iterableValue
     */
    #[\Override]
    public function error(string|array $message, bool $onlyPercentage = false): void
    {
        $this->block($message, ($onlyPercentage ? null : 'ERROR'), 'fg=white;bg=red', ' ', true);
    }

    /**
     * @see SymfonyStyle
     *
     * @inheritDoc
     * @phpstan-ignore missingType.iterableValue
     */
    #[\Override]
    public function success(string|array $message, bool $onlyPercentage = false): void
    {
        $this->block($message, ($onlyPercentage ? null : 'OK'), 'fg=black;bg=green', ' ', true);
    }

    /**
     * @see SymfonyStyle
     *
     * @inheritDoc
     * @phpstan-ignore missingType.iterableValue, missingType.iterableValue
     */
    #[\Override]
    public function table(array $headers, array $rows): void
    {
        $this->createTable()
            ->setHeaders($headers)
            ->setRows($rows)
            ->setColumnMaxWidth(0, 70)
            ->render()
        ;

        $this->newLine();
    }
}
