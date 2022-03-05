<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonSvn\Job;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Repository\SvnRepository;

class LauncherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $logger;
    private $project;
    private $repository;
    private $commit_info;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger      = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->project     = \Mockery::spy(\Project::class);
        $this->repository  = SvnRepository::buildActiveRepository(1, "repository_name", $this->project);
        $this->commit_info = new CommitInfo();
        $this->commit_info->setChangedDirectories(["/", "a", "a/trunk", "a/trunk/b", "a/trunk/c"]);

        $this->project->shouldReceive('getID')->andReturn(101);
        $this->project->shouldReceive('usesService')->with('hudson')->andReturn(true);
    }

    public function tearDown(): void
    {
        unset($this->logger);
        unset($this->project);
        unset($this->repository);
        parent::tearDown();
    }

    private function launchAndTest($jobs, $repository, $commit_info, $call_count)
    {
        $factory      = \Mockery::spy(\Tuleap\HudsonSvn\Job\Factory::class);
        $ci_client    = \Mockery::spy(\Jenkins_Client::class);
        $build_params = new \Tuleap\HudsonSvn\BuildParams($repository, $commit_info);

        $factory->shouldReceive('getJobsByRepository')->andReturn($jobs);
        $ci_client->shouldReceive('launchJobBuild')->times($call_count);

        $launcher = new Launcher($factory, $this->logger, $ci_client, $build_params);

        $launcher->launch($repository, $commit_info);
    }

    public function testItTestJenkinsJobAreTriggeredOnCommit()
    {
        $jobs = [new Job('1', '1', '/', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('2', '1', '/a', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('3', '1', '/a/trunk', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('4', '1', '/*/trunk', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('5', '1', '/*/*', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('6', '1', '/a/*', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('7', '1', '/a/*/b', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('8', '1', '/a/*/c', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 1);

        $jobs = [new Job('9', '1', '/b', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 0);

        $jobs = [new Job('10', '1', '/a/trunked*', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 0);

        $jobs = [new Job('10', '1', '/a/root/c', 'https://ci.exemple.com/job/Job_Example/', '')];
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 0);
    }

    public function testItTestJenkinsJobAreNotLaunchedTwiceOnSameCommit()
    {
        $jobs = [
            new Job('1', '1', '/', 'https://ci.exemple.com/job/Job_Example/', ''),
            new Job('2', '1', '/a/', 'https://ci.exemple.com/job/Job_Example/', ''),
            new Job('3', '1', '/a/*', 'https://ci.exemple.com/job/Job_Example/', ''),
            new Job('4', '1', '/a/trunk', 'https://ci.exemple.com/job/Job_Example/', ''),
            new Job('5', '1', '/a/*/b', 'https://ci.exemple.com/job/Job_Example/', ''),
            new Job('6', '1', '/a/', 'https://ci.exemple.com/job/New_Job_Example/', ''),
            new Job('7', '1', '/a/*', 'https://ci.exemple.com/job/New_Job_Example/', ''),
            new Job('8', '1', '/a/trunk', 'https://ci.exemple.com/job/New_Job_Example/', ''),
            new Job('9', '1', '/', 'https://ci.exemple.com/job/Another_Job_Example/', ''),
        ];

        $this->launchAndTest($jobs, $this->repository, $this->commit_info, 3);
    }
}
