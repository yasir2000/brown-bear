<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
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

namespace Tuleap\Backend;

use Backend;
use BackendSVN;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectManager;
use SVNAccessFile;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBAuthUserConfig;
use Tuleap\ForgeConfigSandbox;

final class BackendSVNTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\GlobalSVNPollution;
    use \Tuleap\GlobalLanguageMock;
    use ForgeConfigSandbox;

    private $tmp_dir;
    private $bin_dir;
    private $fake_revprop;
    private $cache_parameters;
    private $initial_sys_project_backup_path;
    private $initial_svn_root_file;
    private $initial_http_user;
    private $initial_codendi_bin_prefix;
    private $initial_svn_prefix;
    private $initial_tmp_dir;
    private $initial_sys_name;
    private $initial_svnadmin_cmd;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    private \BackendSVN|\Mockery\MockInterface $backend;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp_dir      = $this->getTmpDir();
        $this->bin_dir      = __DIR__ . '/_fixtures';
        $this->fake_revprop = $this->bin_dir . '/post-revprop-change.php';

        $this->initial_svn_prefix = ForgeConfig::get('svn_prefix');
        ForgeConfig::set('svn_prefix', $this->tmp_dir . '/svnroot');
        $this->initial_tmp_dir = ForgeConfig::get('tmp_dir');
        ForgeConfig::set('tmp_dir', $this->tmp_dir . '/tmp');
        $this->initial_sys_name = ForgeConfig::get('sys_name');
        ForgeConfig::set('sys_name', 'Tuleap test');
        $this->initial_svnadmin_cmd = ForgeConfig::get('svnadmin_cmd');
        ForgeConfig::set('svnadmin_cmd', '/usr/bin/svnadmin --config-dir ' . __DIR__ . '/_fixtures/.subversion');

        $this->initial_sys_project_backup_path = ForgeConfig::get('sys_project_backup_path');
        ForgeConfig::set('sys_project_backup_path', $this->tmp_dir . '/backup');
        $this->initial_svn_root_file = ForgeConfig::get('svn_root_file');
        ForgeConfig::set('svn_root_file', $this->getTmpDir() . '/codendi_svnroot.conf');
        $this->initial_http_user = ForgeConfig::get('sys_http_user');
        ForgeConfig::set('sys_http_user', 'codendiadm');
        mkdir(ForgeConfig::get('svn_prefix') . '/toto/hooks', 0777, true);
        mkdir(ForgeConfig::get('tmp_dir'), 0777, true);
        mkdir(ForgeConfig::get('sys_project_backup_path'), 0777, true);
        $this->initial_codendi_bin_prefix = ForgeConfig::get('codendi_bin_prefix');
        ForgeConfig::set('codendi_bin_prefix', $this->bin_dir);

        ForgeConfig::set('sys_custom_dir', $this->tmp_dir);
        mkdir($this->tmp_dir . '/conf');
        ForgeConfig::set(DBAuthUserConfig::USER, 'dbauthuser');
        ForgeConfig::set(DBAuthUserConfig::PASSWORD, ForgeConfig::encryptValue(new ConcealedString('welcome0')));

        $this->project_manager  = \Mockery::spy(\ProjectManager::class);
        $this->cache_parameters = \Mockery::spy(\Tuleap\SvnCore\Cache\Parameters::class);

        $this->backend = \Mockery::mock(\BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }


    protected function tearDown(): void
    {
        //clear the cache between each tests
        Backend::clearInstances();
        ProjectManager::clearInstance();
        ForgeConfig::set('sys_project_backup_path', $this->initial_sys_project_backup_path);
        ForgeConfig::set('svn_root_file', $this->initial_svn_root_file);
        ForgeConfig::set('sys_http_user', $this->initial_http_user);
        ForgeConfig::set('codendi_bin_prefix', $this->initial_codendi_bin_prefix);
        ForgeConfig::set('svn_prefix', $this->initial_svn_prefix);
        ForgeConfig::set('tmp_dir', $this->initial_tmp_dir);
        ForgeConfig::set('sys_name', $this->initial_sys_name);
        ForgeConfig::set('svnadmin_cmd', $this->initial_svnadmin_cmd);
    }

    public function testConstructor(): void
    {
        $this->assertNotNull(BackendSVN::instance());
    }


    public function testArchiveProjectSVN(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestProj');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/TestProj');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $this->backend->shouldReceive('getProjectManager')->andReturns($pm);

        $projdir = ForgeConfig::get('svn_prefix') . "/TestProj";

        // Setup test data
        mkdir($projdir);
        mkdir($projdir . "/db");

        $this->assertEquals($this->backend->archiveProjectSVN(142), true);
        $this->assertFalse(is_dir($projdir), "Project SVN repository should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path') . "/TestProj-svn.tgz"), "SVN Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEquals($this->backend->archiveProjectSVN(99999), false);
    }


    public function testCreateProjectSVN(): void
    {
        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getUserName')->andReturns('user1');
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getUserName')->andReturns('user2');
        $user3 = \Mockery::spy(\PFUser::class);
        $user3->shouldReceive('getUserName')->andReturns('user3');
        $user4 = \Mockery::spy(\PFUser::class);
        $user4->shouldReceive('getUserName')->andReturns('user4');
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestProj');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/TestProj');
        $proj_members = ["0" =>
                               [
                                     "user_name" => "user1",
                                     "user_id"  => "1"],
                              "1" =>
                               [
                                     "user_name" => "user2",
                                     "user_id"  => "2"],
                              "2" =>
                               [
                                     "user_name" => "user3",
                                     "user_id"  => "3"]];
        $project->shouldReceive('getMembersUserNames')->andReturns($proj_members);
        $project->shouldReceive('getMembers')->andReturns([$user1, $user2, $user3]);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $ugroups = ["0" =>
                          [
                                "name" => "QA",
                                "ugroup_id"  => "104"],
                         "1" =>
                          [
                                "name" => "Customers",
                                "ugroup_id"  => "102"]];
        $ugdao   = \Mockery::spy(\UGroupDao::class);
        $ugdao->shouldReceive('searchByGroupId')->andReturns($ugroups);

        $ugroup = \Mockery::spy(\ProjectUGroup::class);
        $ugroup->shouldReceive('getMembersUserName')->andReturn(
            ['user1', 'user2', 'user3'],
            ['user1', 'user4']
        );
        $ugroup->shouldReceive('getMembers')->andReturn(
            [$user1, $user2, $user3],
            [$user1, $user4],
            [$user1, $user4],
            [$user1, $user4],
        );
        $ugroup->shouldReceive('getName')->andReturn('QA', 'QA', 'customers', 'customers');

        $this->backend->shouldReceive('getProjectManager')->andReturns($pm);
        $this->backend->shouldReceive('getUGroupFromRow')->andReturns($ugroup);
        $this->backend->shouldReceive('getUGroupDao')->andReturns($ugdao);

        $access_file = new SVNAccessFile();
        $this->backend->shouldReceive('_getSVNAccessFile')->andReturns($access_file);

        $this->assertEquals($this->backend->createProjectSVN(142), true);
        $this->assertTrue(is_dir(ForgeConfig::get('svn_prefix') . "/TestProj"), "SVN dir should be created");
        $this->assertTrue(is_dir(ForgeConfig::get('svn_prefix') . "/TestProj/hooks"), "hooks dir should be created");
        $this->assertTrue(is_file(ForgeConfig::get('svn_prefix') . "/TestProj/hooks/post-commit"), "post-commit file should be created");
    }

    public function testUpdateSVNAccess(): void
    {
        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getUserName')->andReturns('user1');
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getUserName')->andReturns('user2');
        $user3 = \Mockery::spy(\PFUser::class);
        $user3->shouldReceive('getUserName')->andReturns('user3');
        $user4 = \Mockery::spy(\PFUser::class);
        $user4->shouldReceive('getUserName')->andReturns('user4');
        $user5 = \Mockery::spy(\PFUser::class);
        $user5->shouldReceive('getUserName')->andReturns('user5');
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestProj');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/TestProj');
        $proj_members = ["0" =>
                               [
                                     "user_name" => "user1",
                                     "user_id"  => "1"],
                              "1" =>
                               [
                                     "user_name" => "user2",
                                     "user_id"  => "2"],
                              "2" =>
                               [
                                     "user_name" => "user3",
                                     "user_id"  => "3"]];
        $project->shouldReceive('getMembersUserNames')->andReturns($proj_members);
        $project->shouldReceive('getMembers')->andReturns([$user1, $user2, $user3]);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $ugroups = ["0" =>
                          [
                                "name" => "QA",
                                "ugroup_id"  => "104"],
                         "1" =>
                          [
                                "name" => "Customers",
                                "ugroup_id"  => "102"]];
        $ugdao   = \Mockery::spy(\UGroupDao::class);
        $ugdao->shouldReceive('searchByGroupId')->andReturns($ugroups);

        $ugroup = \Mockery::spy(\ProjectUGroup::class);
        $ugroup->shouldReceive('getMembersUserName')->andReturn(
            ['user1', 'user2', 'user3'],
            ['user1', 'user4'],
            ['user1', 'user2', 'user3'],
            ['user1', 'user4'],
            ['user1', 'user2', 'user3'],
            ['user1', 'user4', 'user5'],
        );
        $ugroup->shouldReceive('getMembers')->andReturn(
            [$user1, $user2, $user3],
            [$user1, $user4],
            [$user1, $user2, $user3],
            [$user1, $user4],
            [$user1, $user2, $user3],
            [$user1, $user4, $user5],
        );
        $ugroup->shouldReceive('getName')->andReturn(
            'QA',
            'QA',
            'QA',
            'QA',
            'QA',
            'QA',
            'customers',
            'customers',
            'customers',
            'customers',
            'customers',
            'customers',
            'customers',
        );

        $this->backend->shouldReceive('getProjectManager')->andReturns($pm);
        $this->backend->shouldReceive('getUGroupFromRow')->andReturns($ugroup);
        $this->backend->shouldReceive('getUGroupDao')->andReturns($ugdao);
        $this->backend->shouldReceive('getSVNAccessGroups')->andReturns("");

        $access_file = new SVNAccessFile();
        $this->backend->shouldReceive('_getSVNAccessFile')->andReturns($access_file);

        $this->assertEquals($this->backend->createProjectSVN(142), true);
        $this->assertDirectoryExists(ForgeConfig::get('svn_prefix') . "/TestProj", "SVN dir should be created");
        $this->assertTrue(is_file(ForgeConfig::get('svn_prefix') . "/TestProj/.SVNAccessFile"), "SVN access file should be created");

        // Update without modification
        $this->assertEquals($this->backend->updateSVNAccess(142, ForgeConfig::get('svn_prefix') . '/TestProj'), true);
        $this->assertTrue(is_file(ForgeConfig::get('svn_prefix') . "/TestProj/.SVNAccessFile"), "SVN access file should exist");
        $this->assertTrue(is_file(ForgeConfig::get('svn_prefix') . "/TestProj/.SVNAccessFile.new"), "SVN access file (.new) should be created");
        $this->assertFalse(is_file(ForgeConfig::get('svn_prefix') . "/TestProj/.SVNAccessFile.old"), "SVN access file (.old) should not be created");
    }

    public function testGenerateSVNApacheConf(): void
    {
        $svn_dao = \Mockery::spy(\SVN_DAO::class)->shouldReceive('searchSvnRepositories')->andReturns(\TestHelper::arrayToDar([
            "group_id"        => "101",
            "group_name"      => "Guinea Pig",
            "unix_group_name" => "gpig",
        ], [
            "group_id"        => "102",
            "group_name"      => "Guinea Pig is \"back\"",
            "unix_group_name" => "gpig2",
        ], [
            "group_id"        => "103",
            "group_name"      => "Guinea Pig is 'angry'",
            "unix_group_name" => "gpig3",
        ]))->getMock();
        $this->backend->shouldReceive('getSvnDao')->andReturns($svn_dao);
        $this->backend->shouldReceive('getProjectManager')->andReturns($this->project_manager);
        $this->backend->shouldReceive('getSVNCacheParameters')->andReturns($this->cache_parameters);

        $this->assertTrue($this->backend->generateSVNApacheConf());
        $svnroots = file_get_contents(ForgeConfig::get('svn_root_file'));

        $this->assertNotFalse($svnroots);
        $this->assertStringContainsString("gpig2", $svnroots, "Project name not found in SVN root");
        $this->assertStringContainsString("AuthName \"Subversion Authorization (Guinea Pig is 'back')\"", $svnroots, "Group name double quotes in realm");
    }

    public function testSetSVNPrivacyPrivate(): void
    {
        $this->backend->shouldReceive('chmod')->with(ForgeConfig::get('svn_prefix') . '/' . 'toto', 0770)->once()->andReturns(true);
        $this->backend->shouldReceive('getProjectManager')->andReturns($this->project_manager);
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('toto');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $this->assertTrue($this->backend->setSVNPrivacy($project, true));
    }

    public function testsetSVNPrivacyPublic(): void
    {
        $this->backend->shouldReceive('chmod')->with(ForgeConfig::get('svn_prefix') . '/' . 'toto', 0775)->once()->andReturns(true);
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('toto');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $this->assertTrue($this->backend->setSVNPrivacy($project, false));
    }

    public function testSetSVNPrivacyNoRepository(): void
    {
        $path_that_doesnt_exist = $this->getTmpDir() . '/' . bin2hex(random_bytes(32));

        $this->backend->shouldReceive('chmod')->never();

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns($path_that_doesnt_exist);
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/' . $path_that_doesnt_exist);

        $this->assertFalse($this->backend->setSVNPrivacy($project, true));
        $this->assertFalse($this->backend->setSVNPrivacy($project, false));
    }

    public function testRenameSVNRepository(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestProj');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/TestProj');

        $project->shouldReceive('getMembersUserNames')->andReturns([]);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $ugdao = \Mockery::spy(\UGroupDao::class);
        $ugdao->shouldReceive('searchByGroupId')->andReturns([]);

        $access_file = new SVNAccessFile();
        $this->backend->shouldReceive('_getSVNAccessFile')->andReturns($access_file);

        $this->backend->shouldReceive('getProjectManager')->andReturns($pm);
        $this->backend->shouldReceive('getUGroupDao')->andReturns($ugdao);
        $this->backend->createProjectSVN(142);

        $this->assertEquals($this->backend->renameSVNRepository($project, "foobar"), true);

        $this->assertTrue(is_dir(ForgeConfig::get('svn_prefix') . "/foobar"), "SVN dir should be renamed");
    }

    public function testUpdateSVNAccessForGivenMember(): void
    {
        $backend = \Mockery::mock(\BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();

        // The user
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns([142]);

        $project1 = \Mockery::spy(\Project::class);
        $project1->shouldReceive('getId')->andReturns(102);

        $project2 = \Mockery::spy(\Project::class);
        $project2->shouldReceive('getId')->andReturns(101);

        $projects =  [102, 101];
        $user->shouldReceive('getAllProjects')->andReturns($projects);

        $pm = \Mockery::spy(\ProjectManager::class);
        $backend->shouldReceive('getProjectManager')->andReturns($pm);

        $pm->shouldReceive('getProject')->with(102)->andReturns($project1);
        $pm->shouldReceive('getProject')->with(101)->andReturns($project2);

        $this->assertEquals($backend->updateSVNAccessForGivenMember($user), true);

        $backend->shouldReceive('repositoryExists')->with($project1)->andReturn(true);
        $backend->shouldReceive('repositoryExists')->with($project2)->andReturn(true);

        $backend->shouldReceive('updateSVNAccess')->with(102)->andReturn(true);
        $backend->shouldReceive('updateSVNAccess')->with(101)->andReturn(true);
    }

    public function testItThrowsAnExceptionIfFileForSymlinkAlreadyExists(): void
    {
        $backend = \Mockery::mock(\BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $path    = ForgeConfig::get('svn_prefix') . '/toto/hooks';
        touch($path . '/post-revprop-change');

        $project = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('toto')->getMock();
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $backend->shouldReceive('log')->once();

        $this->expectException(\BackendSVNFileForSimlinkAlreadyExistsException::class);
        $backend->updateHooks(
            $project,
            ForgeConfig::get('svn_prefix') . '/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'commit-email.pl',
            "",
            "codendi_svn_pre_commit.php"
        );
    }

    public function testDoesntThrowAnExceptionIfTheHookIsALinkToOurImplementation(): void
    {
        $backend = \Mockery::mock(\BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $path    = ForgeConfig::get('svn_prefix') . '/toto/hooks';

        // Create link to fake post-revprop-change
        symlink($this->fake_revprop, $path . '/post-revprop-change');

        $project = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('toto')->getMock();
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $backend->shouldReceive('log')->never();

        $backend->updateHooks(
            $project,
            ForgeConfig::get('svn_prefix') . '/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'commit-email.pl',
            "",
            "codendi_svn_pre_commit.php"
        );
    }
}
