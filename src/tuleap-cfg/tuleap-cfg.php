#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use TuleapCfg\Command\ProcessFactory;

$application = new Application();
$application->add(new \TuleapCfg\Command\ConfigureCommand());
$application->add(new \TuleapCfg\Command\SystemControlCommand(new ProcessFactory()));
$application->add(new \TuleapCfg\Command\StartCommunityEditionContainerCommand(new ProcessFactory()));
$application->add(new \TuleapCfg\Command\SetupMysqlInitCommand(
    new \TuleapCfg\Command\SetupMysql\ConnectionManager(),
    new \TuleapCfg\Command\SetupMysql\DatabaseConfigurator(
        PasswordHandlerFactory::getPasswordHandler(),
        new \TuleapCfg\Command\SetupMysql\ConnectionManager(),
    ),
));
$application->add(new \TuleapCfg\Command\SetupTuleapCommand());
$application->add(new \TuleapCfg\Command\SetupForgeUpgradeCommand());
$application->add(new \TuleapCfg\Command\SiteDeploy\SiteDeployCommand());
$application->add(new \TuleapCfg\Command\SiteDeploy\Images\SiteDeployImagesCommand());
$application->add(new \TuleapCfg\Command\SiteDeploy\FPM\SiteDeployFPMCommand());
$application->add(new \TuleapCfg\Command\SiteDeploy\Gitolite3\SiteDeployGitolite3Command());
$application->add(new \TuleapCfg\Command\SiteDeploy\Nginx\SiteDeployNginxCommand());
$application->add(new \TuleapCfg\Command\SiteDeploy\Apache\SiteDeployApacheCommand());
$application->add(new \TuleapCfg\Command\SiteDeploy\ForgeUpgrade\SiteDeployForgeUpgradeCommand());
$application->run();
