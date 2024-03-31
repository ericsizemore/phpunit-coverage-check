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
/**
 * @internal
 */
require __DIR__ . '/../vendor/autoload.php';

// Setup application
use Esi\CoverageCheck\Application;
use Esi\CoverageCheck\Command\CoverageCheckCommand;
use Esi\CoverageCheck\CoverageCheck;

$command     = new CoverageCheckCommand(new CoverageCheck());
$commandName = $command->getName();

$console = new Application(CoverageCheck::APPLICATION_NAME, CoverageCheck::VERSION);
$console->add($command);
$console->setDefaultCommand($commandName, true);
$console->run();
