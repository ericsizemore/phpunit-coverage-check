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
use Esi\CoverageCheck\Style\CoverageCheckStyle;
use Esi\CoverageCheck\Utils;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
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
class CoverageCheckCommandTest extends TestCase
{
    protected Application $application;

    protected ApplicationTester $tester;

    /**
     * @var string[]
     */
    private static array $fixtures;

    #[\Override]
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
        ];

        $coverageCheckCommand = new CoverageCheckCommand(new CoverageCheck());
        $commandName          = $coverageCheckCommand->getName() ?? 'coverage:check';

        $this->application = new Application();
        $this->application->setAutoExit(false);
        $this->application->add($coverageCheckCommand);
        $this->application->setDefaultCommand($commandName, true);

        $this->tester = new ApplicationTester($this->application);
    }

    public function testCloverFileInvalidRootElement(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['invalid_root'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['invalid_root'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->tester->getDisplay())
        );
        self::assertSame(Command::INVALID, $this->tester->getStatusCode());
    }

    public function testCloverFileNoChildren(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['no_children'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['no_children'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->tester->getDisplay())
        );
        self::assertSame(Command::INVALID, $this->tester->getStatusCode());
    }

    public function testCloverFileNoProjectMetrics(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['no_metrics'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['no_metrics'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->tester->getDisplay())
        );
        self::assertSame(Command::INVALID, $this->tester->getStatusCode());
    }

    public function testRunInvalidCloverFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid input file provided. Was given: (.*?)clovr.xml/');
        $commandTester = new CommandTester($this->application->find('coverage:check'));
        $commandTester->execute([
            'cloverfile' => self::$fixtures['notexist'],
            'threshold'  => 90,
        ]);
    }

    public function testRunInvalidThresholdHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 101 given');
        $commandTester = new CommandTester($this->application->find('coverage:check'));
        $commandTester->execute([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 101,
        ]);
    }

    public function testRunInvalidThresholdLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 0 given');
        $commandTester = new CommandTester($this->application->find('coverage:check'));
        $commandTester->execute([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 0,
        ]);
    }

    public function testRunNotEnoughCode(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['empty'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['empty'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Insufficient data for calculation. Please add more code.',
            trim($this->tester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunNotEnoughCodePercentageOnly(): void
    {
        $this->tester->run([
            'cloverfile'        => self::$fixtures['empty'],
            'threshold'         => 90,
            '--only-percentage' => true,
        ]);

        self::assertEquals(self::$fixtures['empty'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));
        self::assertTrue($this->tester->getInput()->getOption('only-percentage'));

        self::assertSame(
            '[ERROR] Insufficient data for calculation. Please add more code.',
            trim($this->tester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunValidNonPassingOptions(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 100,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(100, $this->tester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[ERROR] Total code coverage is 90.32% which is below the accepted 100%',
            trim($this->tester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunValidOptionsNonPassingPercentageOnly(): void
    {
        $this->tester->run([
            'cloverfile'        => self::$fixtures['valid'],
            'threshold'         => 100,
            '--only-percentage' => true,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(100, $this->tester->getInput()->getArgument('threshold'));
        self::assertTrue($this->tester->getInput()->getOption('only-percentage'));

        self::assertSame(
            '90.32%',
            trim($this->tester->getDisplay())
        );
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunValidOptionsPassing(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertSame(
            '[OK] Total code coverage is 90.32%',
            trim($this->tester->getDisplay())
        );
        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    }

    public function testRunValidOptionsPassingPercentageOnly(): void
    {
        $this->tester->run([
            'cloverfile'        => self::$fixtures['valid'],
            'threshold'         => 90,
            '--only-percentage' => true,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));
        self::assertTrue($this->tester->getInput()->getOption('only-percentage'));

        self::assertSame(
            '90.32%',
            trim($this->tester->getDisplay())
        );
        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    }

    public function testShowFilesTableOutputAboveThreshold(): void
    {
        $this->tester->run([
            'cloverfile'   => self::$fixtures['thisLibrary'],
            'threshold'    => 90,
            '--show-files' => true,
        ]);

        self::assertEquals(self::$fixtures['thisLibrary'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        $eol = PHP_EOL;

        $expected = '------------------------------------------------------------------- --------------------------------- ---------- ' . $eol;
        $expected .= '  File                                                                Covered Elements/Total Elements   Coverage  ' . $eol;
        $expected .= ' ------------------------------------------------------------------- --------------------------------- ---------- ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Application.php                    10/10                             100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Command\CoverageCheckCommand.php   77/77                             100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\CoverageCheck.php                  63/63                             100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Style\CoverageCheckStyle.php       4/4                               100.00%   ' . $eol;
        $expected .= '  [...]\phpunit-coverage-check\src\Utils.php                          16/16                             100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------- --------------------------------- ---------- ' . $eol;
        $expected .= '  Overall Totals                                                      170/170                           100.00%   ' . $eol;
        $expected .= ' ------------------------------------------------------------------- --------------------------------- ----------';

        self::assertEquals($expected, trim($this->tester->getDisplay()));
        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    }

    public function testShowFilesTableOutputBelowThreshold(): void
    {
        $this->tester->run([
            'cloverfile'   => self::$fixtures['valid'],
            'threshold'    => 90,
            '--show-files' => true,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        $eol = PHP_EOL;

        $expected = '----------------------------- --------------------------------- ---------- ' . $eol;
        $expected .= '  File                          Covered Elements/Total Elements   Coverage  ' . $eol;
        $expected .= ' ----------------------------- --------------------------------- ---------- ' . $eol;
        $expected .= '  /tmp/Example/String.php       36/38                             94.74%    ' . $eol;
        $expected .= '  /tmp/Example/StringList.php   20/24                             83.33%    ' . $eol;
        $expected .= ' ----------------------------- --------------------------------- ---------- ' . $eol;
        $expected .= '  Overall Totals                56/62                             89.04%    ' . $eol;
        $expected .= ' ----------------------------- --------------------------------- ----------';

        self::assertEquals($expected, trim($this->tester->getDisplay()));
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testShowFilesTableOutputEmpty(): void
    {
        $this->tester->run([
            'cloverfile'   => self::$fixtures['empty'],
            'threshold'    => 90,
            '--show-files' => true,
        ]);

        self::assertEquals(self::$fixtures['empty'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertSame(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertSame('[ERROR] Insufficient data for calculation. Please add more code.', trim($this->tester->getDisplay()));
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
    }

    /**
     * Could probably be done better, but it works.
     */
    protected static function stripWhitespace(string $output): string
    {
        $output = (string) preg_replace('#\h{2,}#', '', $output);
        $output = (string) preg_replace('#\\n#', ' ', $output);
        $output = str_replace('  ', ' ', $output);

        return trim($output);
    }
}
