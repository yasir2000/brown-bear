<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\ProFTPd\Admin\PermissionsManager;
use Tuleap\ProFTPd\SystemEvent\PROFTPD_UPDATE_ACL;

final class SystemEvent_PROFTPD_UPDATE_ACLTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    /** @var SystemEvent_PROFTPD_DIRECTORY_CREATE */
    private $event;
    /** @var String */
    private $path;
    /** @var String */
    private $mixed_case_group_unix_name;
    /** @var String */
    private $group_unix_name;
    /** @var String */
    private $ftp_directory;
    private $permissions_manager;
    private $project;
    private $project_manager;
    private $acl_updater;

    public function setUp(): void
    {
        parent::setUp();

        $this->event       = $this->createPartialMock(PROFTPD_UPDATE_ACL::class, ['done']);
        $this->acl_updater = $this->getMockBuilder('Tuleap\ProFTPd\Admin\ACLUpdater')->disableOriginalConstructor()->getMock();

        $group_unix_name            = "project_name";
        $mixed_case_group_unix_name = "MiXeDCaSePrOjEcTNaMe";

        $this->group_unix_name            = $group_unix_name;
        $this->mixed_case_group_unix_name = $mixed_case_group_unix_name;

        $this->ftp_directory       = dirname(__FILE__) . '/../_fixtures';
        $this->path                = realpath($this->ftp_directory . "/" . $this->group_unix_name);
        $this->not_mixed_case_path = realpath($this->ftp_directory . "/" . strtolower($this->mixed_case_group_unix_name));

        $this->permissions_manager = $this->getMockBuilder('Tuleap\ProFTPd\Admin\PermissionsManager')->disableOriginalConstructor()->getMock();

        $project       = $this->getMockBuilder('Project')->disableOriginalConstructor()->getMock();
        $this->project = $project;
        $this->project->method('isError')->willReturn(false);
        $this->project
             ->expects($this->any())
             ->method('getUnixName')
             ->will($this->returnValue(strtolower($this->group_unix_name)));
        $this->project
             ->expects($this->any())
             ->method('getUnixNameMixedCase')
             ->will($this->returnValue($this->group_unix_name));

        $mixed_case_project = $this->getMockBuilder('Project')->disableOriginalConstructor()->getMock();
        $mixed_case_project->method('isError')->willReturn(false);
        $mixed_case_project
             ->expects($this->any())
             ->method('getUnixName')
             ->will($this->returnValue(strtolower($this->mixed_case_group_unix_name)));
        $mixed_case_project
             ->expects($this->any())
             ->method('getUnixNameMixedCase')
             ->will($this->returnValue($this->mixed_case_group_unix_name));

        $this->project_manager = $this->getMockBuilder('ProjectManager')->disableOriginalConstructor()->getMock();
        $this->project_manager
             ->expects($this->any())
             ->method('getProjectByUnixName')
             ->will($this->returnCallback(function ($unix_name) use ($group_unix_name, $mixed_case_group_unix_name, $project, $mixed_case_project) {
                switch ($unix_name) {
                    case $group_unix_name:
                        return $project;
                    case strtolower($mixed_case_group_unix_name):
                        return $mixed_case_project;
                }
             }));

        $this->event->injectDependencies($this->acl_updater, $this->permissions_manager, $this->project_manager, $this->ftp_directory);
        ForgeConfig::set('sys_http_user', 'httpuser');
    }

    public function testItSetsACLWithWritersAndReaders()
    {
        $this->event->setParameters($this->group_unix_name);
        $this->permissions_manager
             ->expects($this->any())
             ->method('getUGroupSystemNameFor')
             ->will($this->returnCallback(function ($project, $permission) {
                switch ($permission) {
                    case PermissionsManager::PERM_READ:
                        return 'gpig-ftp_readers';
                    case PermissionsManager::PERM_WRITE:
                        return 'gpig-ftp_writers';
                }
             }));

        $this->acl_updater->expects($this->once())->method('recursivelyApplyACL')->with($this->path, 'httpuser', 'gpig-ftp_writers', 'gpig-ftp_readers');

        $this->event->expects(self::once())->method('done');

        $this->event->process();
    }

    public function testItUsesTheUnixNameInLowerCase()
    {
        $this->event->setParameters(strtolower($this->mixed_case_group_unix_name));
        $this->permissions_manager
             ->expects($this->any())
             ->method('getUGroupSystemNameFor')
             ->will($this->returnCallback(function ($project, $permission) {
                switch ($permission) {
                    case PermissionsManager::PERM_READ:
                        return 'gpig-ftp_readers';
                    case PermissionsManager::PERM_WRITE:
                        return 'gpig-ftp_writers';
                }
             }));

        $this->acl_updater
            ->expects($this->once())
            ->method('recursivelyApplyACL')
            ->with($this->not_mixed_case_path, 'httpuser', 'gpig-ftp_writers', 'gpig-ftp_readers');

        $this->event->expects(self::once())->method('done');

        $this->event->process();
    }
}
