<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalResponseMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PermissionsManagerSavePermissionsCommonTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    protected $permissions_manager;
    protected $project;
    protected $permission_type;
    protected $object_id;
    protected $permissions_dao;
    protected $project_id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project_id          = 404;
        $this->project             = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns($this->project_id)->getMock();
        $this->permissions_dao     = \Mockery::spy(\PermissionsDao::class);
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = new PermissionsManager($this->permissions_dao);
    }

    protected function expectPermissionsOnce($ugroup)
    {
        $this->permissions_dao->shouldReceive('addPermission')
            ->with($this->permission_type, $this->object_id, $ugroup)
            ->once()
            ->andReturns(true);
    }

    protected function savePermissions($ugroups)
    {
        $this->permissions_manager->savePermissions($this->project, $this->object_id, $this->permission_type, $ugroups);
    }

    public function testItSavesTheValueForStaticUGroupId()
    {
        $this->permissions_dao->shouldReceive('clearPermission')
            ->with($this->permission_type, $this->object_id)
            ->once()
            ->andReturns(true);
        $this->permissions_dao->shouldReceive('addPermission')
            ->with($this->permission_type, $this->object_id, 104)
            ->once()
            ->andReturns(true);
        $this->permissions_dao->shouldReceive('addHistory')
            ->with($this->project_id, $this->permission_type, $this->object_id)
            ->once();

        $this->savePermissions([104]);
    }

    public function testItSavesTheValueForSeveralStaticUGroupIds()
    {
        $this->permissions_dao->shouldReceive('clearPermission')
            ->with($this->permission_type, $this->object_id)
            ->once()
            ->andReturns(true);
        $this->permissions_dao->shouldReceive('addPermission')
            ->with($this->permission_type, $this->object_id, 104)
            ->once()
            ->andReturns(true);
        $this->permissions_dao->shouldReceive('addPermission')
            ->with($this->permission_type, $this->object_id, 201)
            ->once()
            ->andReturns(true);
        $this->permissions_dao->shouldReceive('addHistory')
            ->with($this->project_id, $this->permission_type, $this->object_id)
            ->once()
            ->andReturns(true);

        $this->savePermissions([104, 201]);
    }

    public function testItSavesOnlyOneInstanceOfGroups()
    {
        $this->permissions_dao->shouldReceive('clearPermission')->andReturns(true);

        $this->permissions_dao->shouldReceive('addPermission')
            ->with($this->permission_type, $this->object_id, 104)
            ->ordered()
            ->andReturns(true);
        $this->permissions_dao->shouldReceive('addPermission')
            ->with($this->permission_type, $this->object_id, 201)
            ->ordered()
            ->andReturns(true);

        $this->savePermissions([104, 201, 104]);
    }
}
