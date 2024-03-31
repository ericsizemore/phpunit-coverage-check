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
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

use function dirname;
use function trim;

/**
 * @internal
 */
#[CoversClass(CoverageCheckCommand::class)]
#[CoversClass(Application::class)]
#[CoversClass(CoverageCheckStyle::class)]
#[UsesClass(CoverageCheck::class)]
class CoverageCheckCommandTest extends TestCase
{
    protected Application $application;
    protected ApplicationTester $tester;
    private static $fixtures;

    protected function setUp(): void
    {
        self::$fixtures = [
            'valid'        => dirname(__FILE__, 3) . '/fixtures/clover.xml',
            'notexist'     => dirname(__FILE__, 3) . '/fixtures/clovr.xml',
            'empty'        => dirname(__FILE__, 3) . '/fixtures/empty.xml',
            'invalid_root' => dirname(__FILE__, 3) . '/fixtures/invalid_root_element.xml',
            'no_children'  => dirname(__FILE__, 3) . '/fixtures/no_children.xml',
            'no_metrics'   => dirname(__FILE__, 3) . '/fixtures/no_project_metrics.xml',
        ];

        $command     = new CoverageCheckCommand(new CoverageCheck());
        $commandName = $command->getName();

        $this->application = new Application('PHPUnit Coverage Check', CoverageCheck::VERSION);
        $this->application->setAutoExit(false);
        $this->application->add($command);
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
        self::assertEquals(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertEquals(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->tester->getDisplay())
        );
        self::assertEquals(Command::INVALID, $this->tester->getStatusCode());
    }

    public function testCloverFileNoChildren(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['no_children'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['no_children'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertEquals(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertEquals(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->tester->getDisplay())
        );
        self::assertEquals(Command::INVALID, $this->tester->getStatusCode());
    }

    public function testCloverFileNoProjectMetrics(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['no_metrics'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['no_metrics'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertEquals(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertEquals(
            '[ERROR] Clover file appears to be invalid. Are you sure this is a PHPUnit generated clover report?',
            self::stripWhitespace($this->tester->getDisplay())
        );
        self::assertEquals(Command::INVALID, $this->tester->getStatusCode());
    }

    public function testRunInvalidCloverFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid input file provided. Was given: (.*?)clovr.xml/');
        $tester = new CommandTester($this->application->find('coverage:check'));
        $tester->execute([
            'cloverfile' => self::$fixtures['notexist'],
            'threshold'  => 90,
        ]);
    }

    public function testRunInvalidThresholdHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 101 given');
        $tester = new CommandTester($this->application->find('coverage:check'));
        $tester->execute([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 101,
        ]);
    }

    public function testRunInvalidThresholdLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The threshold must be a minimum of 1 and a maximum of 100, 0 given');
        $tester = new CommandTester($this->application->find('coverage:check'));
        $tester->execute([
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
        self::assertEquals(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertEquals(
            '[ERROR] Insufficient data for calculation. Please add more code.',
            trim($this->tester->getDisplay())
        );
        self::assertEquals(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunNotEnoughCodePercentageOnly(): void
    {
        $this->tester->run([
            'cloverfile'        => self::$fixtures['empty'],
            'threshold'         => 90,
            '--only-percentage' => true,
        ], ['verbosity' => Output::VERBOSITY_NORMAL]);

        self::assertEquals(self::$fixtures['empty'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertEquals(90, $this->tester->getInput()->getArgument('threshold'));
        self::assertTrue($this->tester->getInput()->getOption('only-percentage'));

        self::assertEquals(
            '[ERROR] Insufficient data for calculation. Please add more code.',
            trim($this->tester->getDisplay())
        );
        self::assertEquals(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunValidNonPassingOptions(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 100,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertEquals(100, $this->tester->getInput()->getArgument('threshold'));

        self::assertEquals(
            '[ERROR] Total code coverage is 90.32% which is below the accepted 100%',
            trim($this->tester->getDisplay())
        );
        self::assertEquals(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunValidOptionsNonPassingPercentageOnly(): void
    {
        $this->tester->run([
            'cloverfile'        => self::$fixtures['valid'],
            'threshold'         => 100,
            '--only-percentage' => true,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertEquals(100, $this->tester->getInput()->getArgument('threshold'));
        self::assertTrue($this->tester->getInput()->getOption('only-percentage'));

        self::assertEquals(
            '90.32%',
            trim($this->tester->getDisplay())
        );
        self::assertEquals(Command::FAILURE, $this->tester->getStatusCode());
    }

    public function testRunValidOptionsPassing(): void
    {
        $this->tester->run([
            'cloverfile' => self::$fixtures['valid'],
            'threshold'  => 90,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertEquals(90, $this->tester->getInput()->getArgument('threshold'));

        self::assertEquals(
            '[OK] Total code coverage is 90.32%',
            trim($this->tester->getDisplay())
        );
        self::assertEquals(Command::SUCCESS, $this->tester->getStatusCode());
    }

    public function testRunValidOptionsPassingPercentageOnly(): void
    {
        $this->tester->run([
            'cloverfile'        => self::$fixtures['valid'],
            'threshold'         => 90,
            '--only-percentage' => true,
        ]);

        self::assertEquals(self::$fixtures['valid'], $this->tester->getInput()->getArgument('cloverfile'));
        self::assertEquals(90, $this->tester->getInput()->getArgument('threshold'));
        self::assertTrue($this->tester->getInput()->getOption('only-percentage'));

        self::assertEquals(
            '90.32%',
            trim($this->tester->getDisplay())
        );
        self::assertEquals(Command::SUCCESS, $this->tester->getStatusCode());
    }

    protected static function stripWhitespace(string $output): string
    {
        $output = (string) preg_replace('#\h{2,}#', '', $output);
        $output = (string) preg_replace('#\\n#', ' ', $output);
        $output = str_replace('  ', ' ', $string);

        return trim($output);
    }
}
