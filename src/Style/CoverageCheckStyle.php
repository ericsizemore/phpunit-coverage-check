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

use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function max;

/**
 * @see SymfonyStyle
 */
final class CoverageCheckStyle extends SymfonyStyle
{
    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        private int $tableWidth {
            set(int $tableWidth) {
                $this->tableWidth = max(70, $tableWidth);
            }
        }
    ) {
        parent::__construct($input, $output);
    }

    #[Override]
    public function error(array|string $message, bool $onlyPercentage = false): void
    {
        $this->block($message, ($onlyPercentage ? null : 'ERROR'), 'fg=white;bg=red', ' ', true);
    }

    #[Override]
    public function success(array|string $message, bool $onlyPercentage = false): void
    {
        $this->block($message, ($onlyPercentage ? null : 'OK'), 'fg=black;bg=green', ' ', true);
    }

    #[Override]
    public function table(array $headers, array $rows): void
    {
        $this->createTable()
            ->setHeaders($headers)
            ->setRows($rows)
            ->setColumnMaxWidth(0, $this->tableWidth)
            ->render()
        ;

        $this->newLine();
    }
}
