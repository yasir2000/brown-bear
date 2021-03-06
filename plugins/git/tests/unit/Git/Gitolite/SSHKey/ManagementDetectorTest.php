<?php
/**
 * Copyright (c) BrownBear, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey;

use Tuleap\Git\GlobalParameterDao;

final class ManagementDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItCanNotManageAuthorizedKeysFileIfGitolite3IsNotUsed(): void
    {
        $version_detector = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(false);
        $global_parameter_dao = \Mockery::mock(GlobalParameterDao::class);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao);

        $this->assertFalse($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }

    public function testItIsAbleToFindThatTuleapManagesAuthorizedKeysFile(): void
    {
        $version_detector = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(true);
        $global_parameter_dao = \Mockery::mock(GlobalParameterDao::class);
        $global_parameter_dao->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturns(false);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao);

        $this->assertFalse($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }

    public function testItIsAbleToDetectThatTuleapManagesAuthorizedKeysFile(): void
    {
        $version_detector = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(true);
        $global_parameter_dao = \Mockery::mock(GlobalParameterDao::class);
        $global_parameter_dao->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturns(true);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao);

        $this->assertTrue($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }
}
