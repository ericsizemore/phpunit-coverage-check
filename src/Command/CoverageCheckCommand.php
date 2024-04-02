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

namespace Esi\CoverageCheck\Command;

use Esi\CoverageCheck\CoverageCheck;
use Esi\CoverageCheck\Style\CoverageCheckStyle;
use Esi\CoverageCheck\Utils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function is_array;
use function sprintf;

/**
 * @see \Esi\CoverageCheck\Tests\Command\CoverageCheckCommandTest
 */
#[AsCommand(name: 'coverage:check', description: 'Reads the clover xml report from PHPUnit and calculates the coverage score.')]
class CoverageCheckCommand extends Command
{
    private CoverageCheckStyle $coverageCheckStyle;

    public function __construct(private readonly CoverageCheck $coverageCheck)
    {
        parent::__construct();
    }

    /**
     * @see Command
     */
    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('cloverfile', InputArgument::REQUIRED, 'The location of the clover xml file that is generated by PHPUnit.'),
                new InputArgument('threshold', InputArgument::REQUIRED, 'The coverage threshold that is acceptable. Min = 1, Max = 100'),
                new InputOption('--only-percentage', '-O', InputOption::VALUE_NONE, 'Only return the resulting coverage percentage'),
                new InputOption('--show-files', '-F', InputOption::VALUE_NONE, 'Show a breakdown of coverage by file'),
            ])
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> command calculates coverage score for the provided clover xml report.

                    You must also pass a coverage threshold that is acceptable. <info>Min = 1, Max = 100</info>:

                    <info>php %command.full_name% /path/to/clover.xml 100</info>

                    You may also choose to only return the resulting coverage percentage by using the <info>--only-percentage</info> option:

                    <info>php %command.full_name% /path/to/clover.xml 100 --only-percentage</info>

                    You may also choose to show a breakdown of coverage by file by using the <info>--show-files</info> option:

                    <info>php %command.full_name% /path/to/clover.xml 100 --show-files</info>
                    EOF
            )
        ;
    }

    /**
     * @see Command
     * @see CoverageCheck
     * @see CoverageCheckStyle
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->coverageCheckStyle = new CoverageCheckStyle($input, $output);

        /** @var string $cloverFile */
        $cloverFile = $input->getArgument('cloverfile');

        /** @var string $threshold */
        $threshold = $input->getArgument('threshold');

        $this->coverageCheck->setCloverFile($cloverFile)
            ->setThreshold((int) $threshold)
            ->setOnlyPercentage($input->getOption('only-percentage'));

        try {
            $result = $input->getOption('show-files') ? $this->coverageCheck->processByFile() : $this->coverageCheck->process();
        } catch (Throwable $throwable) {
            $this->coverageCheckStyle->error($throwable->getMessage());

            return Command::INVALID;
        }

        // No metrics
        if ($result === false) {
            $this->coverageCheckStyle->error('Insufficient data for calculation. Please add more code.');

            return Command::FAILURE;
        }

        // --show-files
        if (is_array($result)) {
            return $this->getFileTable($result);
        }

        // Standard output
        return $this->getResultOutput($result);
    }

    /**
     * @param array{
     *     totalCoverage: int,
     *     fileMetrics: array<string, array{elements: int, coveredElements: int, percentage: int}>
     * } $result
     */
    private function getFileTable(array $result): int
    {
        $threshold     = $this->coverageCheck->getThreshold();
        $tableRows     = [];
        $totalElements = ['coveredElements' => 0, 'elements' => 0];
        $metrics       = $result['fileMetrics'];
        $totalCoverage = $result['totalCoverage'];

        unset($result);

        foreach ($metrics as $name => $file) {
            $cellFormat = ($file['percentage'] < $threshold) ? '<error>%s</error>' : '<info>%s</info>';

            $tableRows[] = [
                $name,
                sprintf('%d/%d', $file['coveredElements'], $file['elements']),
                new TableCell(
                    Utils::formatCoverage($file['percentage']),
                    ['style' => new TableCellStyle(['cellFormat' => $cellFormat,])]
                ),
            ];

            $totalElements['coveredElements'] += $file['coveredElements'];
            $totalElements['elements']        += $file['elements'];
        }

        $tableRows[] = new TableSeparator();
        $tableRows[] = [
            'Overall Totals',
            sprintf('%d/%d', $totalElements['coveredElements'], $totalElements['elements']),
            new TableCell(
                Utils::formatCoverage($totalCoverage),
                ['style' => new TableCellStyle(['cellFormat' => ($totalCoverage < $threshold) ? '<error>%s</error>' : '<info>%s</info>',])]
            ),
        ];

        $this->coverageCheckStyle->table(
            ['File', 'Covered Elements/Total Elements', 'Coverage'],
            $tableRows
        );

        return ($totalCoverage < $threshold) ? Command::FAILURE : Command::SUCCESS;
    }

    private function getResultOutput(float $result): int
    {
        $threshold         = $this->coverageCheck->getThreshold();
        $onlyPercentage    = $this->coverageCheck->getOnlyPercentage();
        $formattedCoverage = Utils::formatCoverage($result);
        $belowThreshold    = $result < $threshold;

        // Only display the percentage?
        if ($onlyPercentage) {
            // ... below the accepted threshold
            if ($belowThreshold) {
                $this->coverageCheckStyle->error($formattedCoverage, true);

                return Command::FAILURE;
            }

            // all good, we meet or exceed the threshold
            $this->coverageCheckStyle->success($formattedCoverage, true);

            return Command::SUCCESS;
        }

        // We want the full message..
        if ($belowThreshold) {
            // ... below the accepted threshold
            $this->coverageCheckStyle->error(
                sprintf('Total code coverage is %s which is below the accepted %d%%', $formattedCoverage, $threshold)
            );

            return Command::FAILURE;
        }

        // all good, we meet or exceed the threshold
        $this->coverageCheckStyle->success(sprintf('Total code coverage is %s', $formattedCoverage));

        return Command::SUCCESS;
    }
}
