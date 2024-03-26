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

use Esi\CoverageCheck\Command\CoverageCheckCommand;
use Esi\CoverageCheck\CoverageCheck;
use Symfony\Component\Console\Application;

$console = new Application('PHPUnit Coverage Check', CoverageCheck::VERSION);
$console->add(new CoverageCheckCommand(new CoverageCheck()));

return $console;
