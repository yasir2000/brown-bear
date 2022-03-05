<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 */

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Tuleap\Tools\Xml2Php\ConvertTrackerCommand;
use Tuleap\Tools\Xml2Php\ConvertProjectCommand;

require_once __DIR__ . '/vendor/autoload.php';

$application = new Application('xml-templates-to-php');
$application->add(new ConvertTrackerCommand());
$application->add(new ConvertProjectCommand());
$application->run();
