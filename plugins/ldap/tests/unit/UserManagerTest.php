<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SystemEvent;

require_once __DIR__ . '/bootstrap.php';

final class UserManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUpdateLdapUidShouldPrepareRenameOfUserInTheWholePlatform(): void
    {
        // Parameters
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(105);
        $ldap_uid = 'johndoe';

        $lum = \Mockery::mock(\LDAP_UserManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $dao = \Mockery::spy(\LDAP_UserDao::class);
        $dao->shouldReceive('updateLdapUid')->with(105, $ldap_uid)->once()->andReturns(true);
        $lum->shouldReceive('getDao')->andReturns($dao);

        $this->assertTrue($lum->updateLdapUid($user, $ldap_uid));
        $this->assertEquals($lum->getUsersToRename(), [$user]);
    }

    public function testTriggerRenameOfUsersShouldUpdateSVNAccessFileOfProjectWhereTheUserIsMember(): void
    {
        // Parameters
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(105);

        $lum = \Mockery::mock(\LDAP_UserManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $sem = \Mockery::spy(\SystemEventManager::class);
        $sem->shouldReceive('createEvent')->with('PLUGIN_LDAP_UPDATE_LOGIN', '105', SystemEvent::PRIORITY_MEDIUM)->once();
        $lum->shouldReceive('getSystemEventManager')->andReturns($sem);

        $lum->addUserToRename($user);

        $lum->triggerRenameOfUsers();
    }

    public function testTriggerRenameOfUsersWithSeveralUsers(): void
    {
        // Parameters
        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getId')->andReturns(101);
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getId')->andReturns(102);
        $user3 = \Mockery::spy(\PFUser::class);
        $user3->shouldReceive('getId')->andReturns(103);

        $lum = \Mockery::mock(\LDAP_UserManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $sem = \Mockery::spy(\SystemEventManager::class);
        $sem->shouldReceive('createEvent')->with('PLUGIN_LDAP_UPDATE_LOGIN', '101' . SystemEvent::PARAMETER_SEPARATOR . '102' . SystemEvent::PARAMETER_SEPARATOR . '103', SystemEvent::PRIORITY_MEDIUM)->once();
        $lum->shouldReceive('getSystemEventManager')->andReturns($sem);

        $lum->addUserToRename($user1);
        $lum->addUserToRename($user2);
        $lum->addUserToRename($user3);

        $lum->triggerRenameOfUsers();
    }

    public function testTriggerRenameOfUsersWithoutUser(): void
    {
        $lum = \Mockery::mock(\LDAP_UserManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $sem = \Mockery::spy(\SystemEventManager::class);
        $sem->shouldReceive('createEvent')->never();
        $lum->shouldReceive('getSystemEventManager')->andReturns($sem);

        $lum->triggerRenameOfUsers();
    }
}
