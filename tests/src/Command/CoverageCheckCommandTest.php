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

namespace Esi\CoverageCheck\Tests\Command;

use Esi\CoverageCheck\Application;
use Esi\CoverageCheck\Command\CoverageCheckCommand;
use Esi\CoverageCheck\CoverageCheck;
use Esi\CoverageCheck\Exceptions\InvalidInputFileException;
use Esi\CoverageCheck\Exceptions\NotAValidCloverFileException;
use Esi\CoverageCheck\Exceptions\ThresholdOutOfBoundsException;
use Esi\CoverageCheck\Style\CoverageCheckStyle;
use Esi\CoverageCheck\Utils;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

use function preg_replace;
use function str_replace;
use function trim;

use const PHP_EOL;

/**
 * @internal
 */
#[CoversClass(CoverageCheckCommand::class)]
#[CoversClass(Application::class)]
#[CoversClass(CoverageCheckStyle::class)]
#[CoversClass(CoverageCheck::class)]
#[CoversClass(Utils::class)]
#[UsesClass(InvalidInputFileException::class)]
#[UsesClass(ThresholdOutOfBoundsException::class)]
#[UsesClass(NotAValidCloverFileException::class)]
final class CoverageCheckCommandTest extends TestCase
{
    private Application $application;

    private ApplicationTester $applicationTester;

    /**
     * @var string[]
     */
    private static array $fixtures;

    #[Override]
    protected function setUp(): void
    {
        self::$fixtures = [
            'valid'        => \dirname(__FILE__, 3) . '/fixtures/clover.xml',
            'notexist'     => \dirname(__FILE__, 3) . '/fixtures/clovr.xml',
            'empty'        => \dirname(__FILE__, 3) . '/fixtures/empty.xml',
            'invalid_root' => \dirname(__FILE__, 3) . '/fixtures/invalid_root_element.xml',
            'no_children'  => \dirname(__FILE__, 3) . '/fixtures/no_children.xml',
            'no_metrics'   => \dirname(__FILE__, 3) . '/fixtures/no_project_metrics.xml',
            'thisLibrary'  => \dirname(__FILE__, 3) . '/fixtures/self_clover.xml',
            'openClover'   => \dirname(__FILE__, 3) . '/fixtures/open_clover.xml',
        ];

        $coverageCheckCommand = new CoverageCheckCommand(new CoverageCheck());

        $this->application = new Application();
        $this->application->setAutoExit(false);

        if (method_exists($this->application, 'addCommand')) {
            $this->application->addCommand($coverageCheckCommand);
        } else {
            $this->application->add($coverageCheckCommand);
        }

        $this->application->setDefaultCommand(Application::COMMAND_NAME, true);

        $this->applicationTester = new ApplicationTester($this->application);
    }

    public function testCloverFileInvalidRootElement(): void
    {
        $this->applicationTester->run([
            'cloverfile' => self::$fixtures['invalid_root'],
            'threshold'  => 90,
        ]);

        self::assertSame(self::$fixtures['invalid_root'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::INVALID, $this->applicationTester->getStatusCode());
    }

    public function testCloverFileNoChildren(): void
    {
        $this->applicationTester->run([
            'cloverfile' => self::$fixtures['no_children'],
            'threshold'  => 90,
        ]);

        self::assertSame(self::$fixtures['no_children'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::INVALID, $this->applicationTester->getStatusCode());
    }

    public function testCloverFileNoProjectMetrics(): void
    {
        $this->applicationTester->run([
            'cloverfile' => self::$fixtures['no_metrics'],
            'threshold'  => 90,
        ]);

        self::assertSame(self::$fixtures['no_metrics'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::INVALID, $this->applicationTester->getStatusCode());
    }

    public function testRunInvalidCloverFile(): void
    {
        $this->expectException(InvalidInputFileException::class);
        $this->expectExceptionMessageMatches('/Invalid input file provided. Was given: (.*?)clovr.xml/');
        $commandTester = new CommandTester($this->application->find('coverage:check'));
        $commandTester->execute([
            'cloverfile' => self::$fixtures['notexist'],
            'threshold'  => 90,
        ]);
    }

    public function testRunInvalidThresholdHigh(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 101 given');
        $commandTester = new CommandTester($this->application->find('coverage:check'));
        $commandTester->execute([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 101,
        ]);
    }

    public function testRunInvalidThresholdLow(): void
    {
        $this->expectException(ThresholdOutOfBoundsException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 0 given');
        $commandTester = new CommandTester($this->application->find('coverage:check'));
        $commandTester->execute([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 0,
        ]);
    }

    public function testRunNotEnoughCode(): void
    {
        $this->applicationTester->run([
            'cloverfile' => self::$fixtures['empty'],
            'threshold'  => 90,
        ]);

        self::assertSame(self::$fixtures['empty'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        self::assertSame(
            CoverageCheck::ERROR_INSUFFICIENT_DATA,
            trim($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->applicationTester->getStatusCode());
    }

    public function testRunNotEnoughCodePercentageOnly(): void
    {
        $this->applicationTester->run([
            'cloverfile'        => self::$fixtures['empty'],
            'threshold'         => 90,
            '--only-percentage' => true,
        ]);

        self::assertSame(self::$fixtures['empty'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));
        self::assertTrue($this->applicationTester->getInput()->getOption('only-percentage'));

        self::assertSame(
            CoverageCheck::ERROR_INSUFFICIENT_DATA,
            trim($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->applicationTester->getStatusCode());
    }

    public function testRunValidNonPassingOptions(): void
    {
        $this->applicationTester->run([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 100,
        ]);

        self::assertSame(self::$fixtures['valid'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(100, $this->applicationTester->getInput()->getArgument('threshold'));

        self::assertSame(
            \sprintf(CoverageCheck::ERROR_COVERAGE_BELOW_THRESHOLD, 90.32, 100),
            trim($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->applicationTester->getStatusCode());
    }

    public function testRunValidOptionsNonPassingPercentageOnly(): void
    {
        $this->applicationTester->run([
            'cloverfile'        => self::$fixtures['valid'],
            'threshold'         => 100,
            '--only-percentage' => true,
        ]);

        self::assertSame(self::$fixtures['valid'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(100, $this->applicationTester->getInput()->getArgument('threshold'));
        self::assertTrue($this->applicationTester->getInput()->getOption('only-percentage'));

        self::assertSame(
            '90.32%',
            trim($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->applicationTester->getStatusCode());
    }

    public function testRunValidOptionsPassing(): void
    {
        $this->applicationTester->run([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 90,
        ]);

        self::assertSame(self::$fixtures['valid'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        self::assertSame(
            \sprintf(CoverageCheck::OK_TOTAL_CODE_COVERAGE, 90.32),
            trim($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::SUCCESS, $this->applicationTester->getStatusCode());
    }

    public function testRunValidOptionsPassingPercentageOnly(): void
    {
        $this->applicationTester->run([
            'cloverfile'        => self::$fixtures['valid'],
            'threshold'         => 90,
            '--only-percentage' => true,
        ]);

        self::assertSame(self::$fixtures['valid'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));
        self::assertTrue($this->applicationTester->getInput()->getOption('only-percentage'));

        self::assertSame(
            '90.32%',
            trim($this->applicationTester->getDisplay())
        );
        self::assertSame(Command::SUCCESS, $this->applicationTester->getStatusCode());
    }

    public function testShowFilesTableOutputAboveThreshold(): void
    {
        $this->applicationTester->run([
            'cloverfile'   => self::$fixtures['thisLibrary'],
            'threshold'    => 90,
            '--show-files' => true,
        ]);

        self::assertSame(self::$fixtures['thisLibrary'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        $eol = PHP_EOL;

        $expected = '------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  File                                                                     Elements (Covered/Total)   Coverage  ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Application.php                         12/12                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\CoverageCheck.php                       82/82                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Utils.php                               37/37                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Command\CoverageCheckCommand.php        79/79                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\InvalidInputFileException.   2/2                        100.00%   ' . $eol;
        $expected .= '  php                                                                                                           ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\NotAValidCloverFileExcepti   2/2                        100.00%   ' . $eol;
        $expected .= '  on.php                                                                                                        ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\ThresholdOutOfBoundsExcept   2/2                        100.00%   ' . $eol;
        $expected .= '  ion.php                                                                                                       ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Style\CoverageCheckStyle.php            14/14                      100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  Overall Totals                                                           230/230                    100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ----------';

        self::assertSame($expected, trim($this->applicationTester->getDisplay()));
        self::assertSame(Command::SUCCESS, $this->applicationTester->getStatusCode());
    }

    public function testShowFilesTableOutputBelowThreshold(): void
    {
        $this->applicationTester->run([
            'cloverfile'   => self::$fixtures['valid'],
            'threshold'    => 91,
            '--show-files' => true,
        ]);

        self::assertSame(self::$fixtures['valid'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(91, $this->applicationTester->getInput()->getArgument('threshold'));

        $eol = PHP_EOL;

        $expected = '----------------------------- -------------------------- ---------- ' . $eol;
        $expected .= '  File                          Elements (Covered/Total)   Coverage  ' . $eol;
        $expected .= ' ----------------------------- -------------------------- ---------- ' . $eol;
        $expected .= '  /tmp/Example/String.php       36/38                      94.74%    ' . $eol;
        $expected .= '  /tmp/Example/StringList.php   20/24                      83.33%    ' . $eol;
        $expected .= ' ----------------------------- -------------------------- ---------- ' . $eol;
        $expected .= '  Overall Totals                56/62                      90.32%    ' . $eol;
        $expected .= ' ----------------------------- -------------------------- ----------';

        self::assertSame($expected, trim($this->applicationTester->getDisplay()));
        self::assertSame(Command::FAILURE, $this->applicationTester->getStatusCode());
    }

    public function testShowFilesTableOutputCloverAndOpenCloverTheSame(): void
    {
        $this->applicationTester->run([
            'cloverfile'   => self::$fixtures['thisLibrary'],
            'threshold'    => 90,
            '--show-files' => true,
        ]);

        self::assertSame(self::$fixtures['thisLibrary'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        $eol = PHP_EOL;

        $expected = '------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  File                                                                     Elements (Covered/Total)   Coverage  ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Application.php                         12/12                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\CoverageCheck.php                       82/82                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Utils.php                               37/37                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Command\CoverageCheckCommand.php        79/79                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\InvalidInputFileException.   2/2                        100.00%   ' . $eol;
        $expected .= '  php                                                                                                           ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\NotAValidCloverFileExcepti   2/2                        100.00%   ' . $eol;
        $expected .= '  on.php                                                                                                        ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\ThresholdOutOfBoundsExcept   2/2                        100.00%   ' . $eol;
        $expected .= '  ion.php                                                                                                       ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Style\CoverageCheckStyle.php            14/14                      100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  Overall Totals                                                           230/230                    100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ----------';

        self::assertSame($expected, trim($this->applicationTester->getDisplay()));
        self::assertSame(Command::SUCCESS, $this->applicationTester->getStatusCode());

        $this->applicationTester->run([
            'cloverfile'   => self::$fixtures['openClover'],
            'threshold'    => 90,
            '--show-files' => true,
        ]);

        self::assertSame($expected, trim($this->applicationTester->getDisplay()));
    }

    public function testShowFilesTableOutputEmpty(): void
    {
        $this->applicationTester->run([
            'cloverfile'   => self::$fixtures['empty'],
            'threshold'    => 90,
            '--show-files' => true,
        ]);

        self::assertSame(self::$fixtures['empty'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        self::assertSame(CoverageCheck::ERROR_INSUFFICIENT_DATA, trim($this->applicationTester->getDisplay()));
        self::assertSame(Command::FAILURE, $this->applicationTester->getStatusCode());
    }

    /**
     * @todo This test needs an example output that actually will display a table width of 100
     */
    public function testShowFilesTableOutputWithDifferentTableWidth(): void
    {
        $this->applicationTester->run([
            'cloverfile'    => self::$fixtures['thisLibrary'],
            'threshold'     => 90,
            '--table-width' => 100,
            '--show-files'  => true,
        ]);

        self::assertSame(self::$fixtures['thisLibrary'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        $eol = PHP_EOL;

        $expected = '------------------------------------------------------------------------------- -------------------------- ---------- ' . $eol;
        $expected .= '  File                                                                            Elements (Covered/Total)   Coverage  ' . $eol;
        $expected .= ' ------------------------------------------------------------------------------- -------------------------- ---------- ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Application.php                                12/12                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\CoverageCheck.php                              82/82                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Utils.php                                      37/37                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Command\CoverageCheckCommand.php               79/79                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\InvalidInputFileException.php       2/2                        100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\NotAValidCloverFileException.php    2/2                        100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\ThresholdOutOfBoundsException.php   2/2                        100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Style\CoverageCheckStyle.php                   14/14                      100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------------- -------------------------- ---------- ' . $eol;
        $expected .= '  Overall Totals                                                                  230/230                    100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------------- -------------------------- ----------';

        self::assertSame($expected, trim($this->applicationTester->getDisplay()));
        self::assertSame(Command::SUCCESS, $this->applicationTester->getStatusCode());
    }

    public function testShowFilesTableOutputWithDifferentTableWidthLessThanMin(): void
    {
        $this->applicationTester->run([
            'cloverfile'    => self::$fixtures['thisLibrary'],
            'threshold'     => 90,
            '--table-width' => 60,
            '--show-files'  => true,
        ]);

        self::assertSame(self::$fixtures['thisLibrary'], $this->applicationTester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->applicationTester->getInput()->getArgument('threshold'));

        $eol = PHP_EOL;

        $expected = '------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  File                                                                     Elements (Covered/Total)   Coverage  ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Application.php                         12/12                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\CoverageCheck.php                       82/82                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Utils.php                               37/37                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Command\CoverageCheckCommand.php        79/79                      100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\InvalidInputFileException.   2/2                        100.00%   ' . $eol;
        $expected .= '  php                                                                                                           ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\NotAValidCloverFileExcepti   2/2                        100.00%   ' . $eol;
        $expected .= '  on.php                                                                                                        ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Exceptions\ThresholdOutOfBoundsExcept   2/2                        100.00%   ' . $eol;
        $expected .= '  ion.php                                                                                                       ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Style\CoverageCheckStyle.php            14/14                      100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ---------- ' . $eol;
        $expected .= '  Overall Totals                                                           230/230                    100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------------ -------------------------- ----------';

        self::assertSame($expected, trim($this->applicationTester->getDisplay()));
        self::assertSame(Command::SUCCESS, $this->applicationTester->getStatusCode());
    }

    /**
     * Could probably be done better, but it works.
     */
    private static function stripWhitespace(string $output): string
    {
        $output = (string) preg_replace('#\h{2,}#', '', $output);
        $output = (string) preg_replace('#\\n#', ' ', $output);
        $output = str_replace('  ', ' ', $output);

        return trim($output);
    }
}
