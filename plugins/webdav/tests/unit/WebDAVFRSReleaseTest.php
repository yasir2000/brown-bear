<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

namespace Tuleap\WebDAV;

use FRSFileFactory;
use FRSPackage;
use FRSRelease;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\RequestedRangeNotSatisfiable;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

/**
 * This is the unit test of WebDAVFRSRelease
 */
class WebDAVFRSReleaseTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\WebDAVUtils
     */
    private $utils;

    protected function setUp(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures/incoming');

        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->build();
        $this->utils   = Mockery::mock(\WebDAVUtils::class);
    }

    /**
     * Testing when The release have no files
     */
    public function testGetChildrenNoFiles(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('getFileList')->andReturns([]);

        $this->assertEquals([], $webDAVFRSRelease->getChildren());
    }

    /**
     * Testing when the release contains files
     */
    public function testGetChildrenContainFiles(): void
    {
        $file = \Mockery::spy(\WebDAVFRSFile::class);

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('getChild')->andReturns($file);

        $FRSFile = \Mockery::spy(\FRSFile::class);
        $webDAVFRSRelease->shouldReceive('getFileList')->andReturns([$FRSFile]);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($file);

        $this->assertEquals([$file], $webDAVFRSRelease->getChildren());
    }

    /**
     * Testing when the file is null
     */
    public function testGetChildFailureWithFileNull(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(0);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturnNull();

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file returns isActive == false
     */
    public function testGetChildFailureWithNotActive(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $file             = \Mockery::spy(\FRSFile::class);

        $file->shouldReceive('isActive')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the user don't have the right to download
     */
    public function testGetChildFailureWithUserCanNotDownload(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $file             = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('isActive')->andReturns(true);
        $file->shouldReceive('userCanDownload')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file doesn't exist
     */
    public function testGetChildFailureWithNotExist(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $file             = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('isActive')->andReturns(true);
        $file->shouldReceive('userCanDownload')->andReturns(true);
        $file->shouldReceive('fileExists')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file don't belong to the given package
     */
    public function testGetChildFailureWithNotBelongToPackage(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFile')->andReturns($file);
        $file->shouldReceive('isActive')->andReturns(true);
        $file->shouldReceive('userCanDownload')->andReturns(true);
        $file->shouldReceive('fileExists')->andReturns(true);
        $file->shouldReceive('getPackageId')->andReturns(1);
        $file->shouldReceive('getReleaseId')->andReturns(3);

        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(2);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(3);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file don't belong to the given relaese
     */
    public function testGetChildFailureWithNotBelongToRelease(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('isActive')->andReturns(true);
        $file->shouldReceive('userCanDownload')->andReturns(true);
        $file->shouldReceive('fileExists')->andReturns(true);
        $file->shouldReceive('getPackageId')->andReturns(1);
        $file->shouldReceive('getReleaseId')->andReturns(2);

        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(3);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file size exceed max file size
     */
    public function testGetChildFailureWithBigFile(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFile')->andReturns($file);
        $file->shouldReceive('isActive')->andReturns(true);
        $file->shouldReceive('userCanDownload')->andReturns(true);
        $file->shouldReceive('fileExists')->andReturns(true);
        $file->shouldReceive('getPackageId')->andReturns(1);
        $file->shouldReceive('getReleaseId')->andReturns(2);
        $file->shouldReceive('getFileSize')->andReturns(65);

        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(2);

        $webDAVFRSRelease->shouldReceive('getMaxFileSize')->andReturns(64);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException(RequestedRangeNotSatisfiable::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when GetChild succeed
     */
    public function testGetChildSucceed(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class, [$this->user, $this->project, null, null, 1000])->makePartial()->shouldAllowMockingProtectedMethods();

        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('isActive')->andReturns(true);
        $file->shouldReceive('userCanDownload')->andReturns(true);
        $file->shouldReceive('fileExists')->andReturns(true);
        $file->shouldReceive('getPackageId')->andReturns(1);
        $file->shouldReceive('getReleaseId')->andReturns(2);
        $file->shouldReceive('getFileSize')->andReturns(64);

        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(2);

        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $webDAVFRSRelease->shouldReceive('getUtils')->andReturn($this->utils);

        $webDAVFile = new \WebDAVFRSFile($this->user, $this->project, $file, $this->utils);
        $this->assertEquals($webDAVFile, $webDAVFRSRelease->getChild('fileName'));
    }

    /**
     * Testing when the release is deleted and the user have no permissions
     */
    public function testUserCanReadFailureReleaseDeletedUserHaveNoPermissions(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active and user can not read
     */
    public function testUserCanReadFailureActiveUserCanNotRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is not active and the user can read
     */
    public function testUserCanReadFailureDeletedUserCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active and the user can read
     */
    public function testUserCanReadSucceedActiveUserCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is hidden and the user is not admin an can not read
     */
    public function testUserCanReadFailureHiddenNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('userIsAdmin')->andReturnFalse();
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is hidden and the user can read and is not admin
     */
    public function testUserCanReadFailureHiddenNotAdminUserCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(true);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is deleted and the user is admin
     */
    public function testUserCanReadFailureDeletedUserIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active but the admin can not read ????
     * TODO: verify this in a real case
     */
    public function testUserCanReadFailureAdminHaveNoPermission(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is deleted and user is admin and can read
     */
    public function testUserCanReadFailureDeletedCanReadIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is active and user can read and is admin
     */
    public function testUserCanReadSucceedActiveUserCanReadIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is hidden and user is admin
     */
    public function testUserCanReadSucceedHiddenUserIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(true);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is hidden and user is admin and can read
     */
    public function testUserCanReadSucceedHiddenUserIsAdminCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release          = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(true);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(false);
        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing delete when release doesn't exist
     */
    public function testDeleteReleaseNotExist(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('delete_release')->andReturns(0);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(0);

        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing succeeded delete
     */
    public function testDeleteSucceede(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('delete_release')->andReturns(1);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(1);

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing setName when user is not admin
     */
    public function testSetNameFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(false);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->setName('newName');
    }

    /**
     * Testing setName when name already exist
     */
    public function testSetNameFailWithNameExist(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $this->expectException(MethodNotAllowed::class);

        $webDAVFRSRelease->setName('newName');
    }

    /**
     * Testing setName succeede
     */
    public function testSetNameSucceede(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);

        $webDAVFRSRelease->setName('newName');
    }

    public function testMoveFailNotAdminOnSource(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException(Forbidden::class);

        $source->move($destination);
    }

    public function testMoveFailNotAdminOnDestination(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(false);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException(Forbidden::class);

        $source->move($destination);
    }

    public function testMoveFailNotAdminOnBoth(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(false);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException(Forbidden::class);

        $source->move($destination);
    }

    public function testMoveFailNameExist(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException(MethodNotAllowed::class);

        $source->move($destination);
    }

    public function testMoveFailPackageHiddenReleaseNotHidden(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(false);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException(MethodNotAllowed::class);

        $source->move($destination);
    }

    public function testMoveSucceedPackageAndReleaseHidden(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(true);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $source->move($destination);
    }

    public function testMoveSucceedReleaseHidden(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(true);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $source->move($destination);
    }

    public function testMoveSucceed(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf  = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(false);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $source->move($destination);
    }

    /**
     * Testing creation of file when user is not admin
     */
    public function testCreateFileFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(false);
        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->createFile('release');
    }

    /**
     * Testing creation of file when the file size is bigger than permitted
     */
    public function testCreateFileFailWithFileSizeLimitExceeded(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', \org\bovigo\vfs\vfsStream::setup()->url());

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturns($frsff);
        $utils->shouldReceive('getIncomingFileSize')->andReturns(65);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $this->expectException(RequestedRangeNotSatisfiable::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->shouldReceive('getMaxFileSize')->andReturns(64);

        $webDAVFRSRelease->createFile('release1', $data);
    }

    /**
     * Testing creation of file succeed
     */
    public function testCreateFilesucceed(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', \org\bovigo\vfs\vfsStream::setup()->url());

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $frsff->shouldReceive('createFile')->andReturn(true);

        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('getReleaseID')->andReturns(1234);
        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);

        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('emailNotification')->once();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturns($frsff);
        $utils->shouldReceive('getIncomingFileSize')->andReturns(64);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);

        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $user = \Mockery::spy(\PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);

        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->shouldReceive('getMaxFileSize')->andReturns(64);

        $webDAVFRSRelease->createFile('release', $data);
    }

    public function testCreateFileIntoIncomingUnlinkFail(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->once()->andReturns(false);
        $webDAVFRSRelease->shouldReceive('openFile')->never();
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->never();
        $webDAVFRSRelease->shouldReceive('closeFile')->never();
        $this->expectException(Exception::class);

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }

    public function testCreateFileIntoIncomingCreateFail(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->never()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(false);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->never();
        $webDAVFRSRelease->shouldReceive('closeFile')->never();
        $this->expectException(Exception::class);

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testCreateFileIntoIncomingCloseFail(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->never()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->once();
        $webDAVFRSRelease->shouldReceive('closeFile')->once()->andReturns(false);
        $this->expectException(Exception::class);
        $this->expectException(Exception::class);

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testCreateFileIntoIncomingSucceed(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->never();
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->once();
        $webDAVFRSRelease->shouldReceive('closeFile')->once()->andReturns(true);

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testCreateFileIntoIncomingSucceedWithFileExist(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->once();
        $webDAVFRSRelease->shouldReceive('closeFile')->once()->andReturns(true);

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }
}
