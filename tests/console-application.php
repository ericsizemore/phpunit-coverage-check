<?php

declare(strict_types=1);

/**
 * This file is part of PHPUnit Coverage Check.
 *
 * (c) Eric Sizemore <admin@secondversion.com>
 * (c) Richard Regeer <rich2309@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
require __DIR__ . '/../vendor/autoload.php';

use Esi\CoverageCheck\Command\CoverageCheckCommand;
use Esi\CoverageCheck\CoverageCheck;
use Symfony\Component\Console\Application;

$console = new Application('PHPUnit Coverage Check', CoverageCheck::VERSION);
$console->add(new CoverageCheckCommand(new CoverageCheck()));

return $console;
