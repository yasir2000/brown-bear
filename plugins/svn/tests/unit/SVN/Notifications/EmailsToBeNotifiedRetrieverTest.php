<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Notifications;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use ProjectUGroup;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Repository\Repository;

class EmailsToBeNotifiedRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PFUser
     */
    private $user_suspended;
    /**
     * @var PFUser
     */
    private $user_jdoe;
    /**
     * @var PFUser
     */
    private $user_charles;
    /**
     * @var PFUser
     */
    private $user_jsmith;
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var MailNotificationManager
     */
    private $notification_manager;

    /**
     * @var EmailsToBeNotifiedRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository           = \Mockery::spy(\Tuleap\SVN\Repository\Repository::class);
        $this->notification_manager = \Mockery::spy(\Tuleap\SVN\Admin\MailNotificationManager::class);
        $this->user_dao             = \Mockery::spy(\Tuleap\SVN\Notifications\UsersToNotifyDao::class);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(222);

        $this->repository->shouldReceive('getProject')->andReturn($project);

        $notified_ugroups_dao = \Mockery::spy(\Tuleap\SVN\Notifications\UgroupsToNotifyDao::class);
        $ugroup_manager       = \Mockery::spy(\UGroupManager::class);

        $this->user_jsmith = \Mockery::spy(\PFUser::class);
        $this->user_jsmith->shouldReceive('getEmail')->andReturn('jsmith@example.com');
        $this->user_jsmith->shouldReceive('isAlive')->andReturn(true);

        $this->user_charles = \Mockery::spy(\PFUser::class);
        $this->user_charles->shouldReceive('getEmail')->andReturn('charles@example.com');
        $this->user_charles->shouldReceive('isAlive')->andReturn(true);

        $this->user_jdoe = \Mockery::spy(\PFUser::class);
        $this->user_jdoe->shouldReceive('getEmail')->andReturn('jdoe@example.com');
        $this->user_jdoe->shouldReceive('isAlive')->andReturn(true);

        $this->user_suspended = \Mockery::spy(\PFUser::class);
        $this->user_suspended->shouldReceive('getEmail')->andReturn('suspended@example.com');
        $this->user_suspended->shouldReceive('isAlive')->andReturn(false);

        $this->retriever = new EmailsToBeNotifiedRetriever(
            $this->notification_manager
        );
    }

    public function testItReturnsEmailsAsArray(): void
    {
        $this->notification_manager->shouldReceive('getByPath')->andReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    ['jdoe@example.com', 'jsmith@example.com'],
                    [],
                    []
                ),
            ]
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = ['jdoe@example.com', 'jsmith@example.com'];

        $this->assertEquals($emails, $expected);
    }

    public function testItCombinesEmailsFromMultipleMatchingNotifications(): void
    {
        $this->notification_manager->shouldReceive('getByPath')->andReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [],
                    []
                ),
                new MailNotification(
                    2,
                    $this->repository,
                    '/path',
                    ['jdoe@example.com'],
                    [],
                    []
                ),
            ]
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = ['jdoe@example.com', 'jsmith@example.com'];

        $this->assertEquals($emails, $expected);
    }

    public function testItReturnsEmailsOfUsersForNotification(): void
    {
        $this->notification_manager->shouldReceive('getByPath')->andReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    [],
                    [$this->user_jsmith],
                    []
                ),
            ]
        );
        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = ['jsmith@example.com'];

        $this->assertEquals($emails, $expected);
    }

    public function testItReturnsEmailsOfUgroupMembersForNotification(): void
    {
        $user_group = \Mockery::mock(ProjectUGroup::class);
        $user_group->shouldReceive('getMembers')->andReturn([$this->user_charles, $this->user_jdoe]);
        $this->notification_manager->shouldReceive('getByPath')->andReturn(
            [
                new MailNotification(
                    101,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [],
                    [$user_group]
                ),
            ]
        );

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('charles@example.com', $emails));
    }

    public function testItRemovesGroupMembersThatAreNotAlive(): void
    {
        $user_group = \Mockery::mock(ProjectUGroup::class);
        $user_group->shouldReceive('getMembers')->andReturn([$this->user_suspended]);
        $this->notification_manager->shouldReceive('getByPath')->andReturn(
            [
                new MailNotification(
                    101,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [],
                    [$user_group]
                ),
            ]
        );
        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $this->assertTrue(! in_array('suspended@example.com', $emails));
    }

    public function testItRemovesDuplicates(): void
    {
        $user_group = \Mockery::mock(ProjectUGroup::class);
        $user_group->shouldReceive('getMembers')->andReturn([$this->user_jsmith]);
        $this->notification_manager->shouldReceive('getByPath')->andReturn(
            [
                new MailNotification(
                    1,
                    $this->repository,
                    '/path',
                    ['jsmith@example.com'],
                    [$this->user_jsmith],
                    [$user_group]
                ),
            ]
        );
        $this->user_dao->shouldReceive('searchUsersByNotificationId')->andReturn(['email' => 'jsmith@example.com']);

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $this->assertEquals($emails, array_unique($emails));
    }
}
