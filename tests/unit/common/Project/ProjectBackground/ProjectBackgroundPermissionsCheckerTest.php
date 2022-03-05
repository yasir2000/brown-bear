<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\ProjectBackground;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class ProjectBackgroundPermissionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectBackgroundPermissionsChecker
     */
    private $permissions_checker;

    protected function setUp(): void
    {
        $this->permissions_checker = new ProjectBackgroundPermissionsChecker();
    }

    public function testPermissionIsGrantedWhenTheUserIsProjectAdmin(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(true);

        $project    = \Project::buildForTest();
        $permission = $this->permissions_checker->getModifyProjectBackgroundPermission($project, $user);
        self::assertNotNull($permission);
        self::assertSame($project, $permission->getProject());
    }

    public function testPermissionIsDeniedWhenTheUserIsNotProjectAdmin(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(false);

        $permission = $this->permissions_checker->getModifyProjectBackgroundPermission(
            \Project::buildForTest(),
            $user
        );
        self::assertNull($permission);
    }
}
