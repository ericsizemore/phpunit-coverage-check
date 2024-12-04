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

namespace Esi\CoverageCheck;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function getenv;

/**
 * @internal
 */
final class Application extends BaseApplication
{
    /**
     * Constants used in the Console Application for library information.
     */
    public const APPLICATION_DESCRIPTION = 'Reads the clover xml report from PHPUnit and calculates the coverage score.';

    public const APPLICATION_NAME = 'PHPUnit Coverage Check';

    public const COMMAND_NAME = 'coverage:check';

    public const VERSION = '2.0.3';

    /**
     * Override constructor.
     */
    public function __construct()
    {
        parent::__construct(self::APPLICATION_NAME, self::VERSION);
    }

    /**
     * Same as {@see self::getDefaultInputDefinition()}, but overriding configureIO().
     *
     * @see BaseApplication::configureIO()
     *
     * @inheritDoc
     */
    #[\Override]
    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(!(bool) getenv('PHPUNIT_TEST'));
        $input->setInteractive(false);
    }

    /**
     * Override's getDefaultInputDefinition() to clean up the output of the --help option.
     * By default, (without this override) it shows Symfony specific information along with
     * our command's information, much of which is not needed.
     *
     * @see BaseApplication::getDefaultInputDefinition()
     *
     * @inheritDoc
     */
    #[\Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition(
            [
                new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
                new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this library version'),
            ]
        );
    }
}
