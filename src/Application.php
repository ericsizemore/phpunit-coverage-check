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
     * Application / library name. (used in the Console Application).
     */
    public const APPLICATION_NAME = 'PHPUnit Coverage Check';

    /**
     * Current library version. (used in the Console Application).
     */
    public const VERSION = '2.0.0';

    public function __construct()
    {
        parent::__construct(self::APPLICATION_NAME, self::VERSION);
    }

    /**
     * Same as {@see self::getDefaultInputDefinition()}, but overriding configureIO().
     *
     * @see \Symfony\Component\Console\Application::configureIO()
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
     * @see \Symfony\Component\Console\Application::getDefaultInputDefinition()
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
