<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Sabre\DAV\Exception\Forbidden;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use WebDAVFRSFile;
use WebDAVUtils;

final class WebDAVFRSFileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->build();
    }

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $utils = Mockery::mock(WebDAVUtils::class);
        $utils->shouldReceive('userCanWrite')->with($this->user, $this->project->getID())->once()->andReturnFalse();
        $webDAVFile = new WebDAVFRSFile($this->user, $this->project, new \FRSFile(['file_id' => 4]), $utils);

        $this->expectException(Forbidden::class);

        $webDAVFile->delete();
    }

    /**
     * Testing delete when file doesn't exist
     */
    public function testDeleteFileNotExist(): void
    {
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('delete_file')->andReturn(0);
        $utils = Mockery::mock(WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturn($frsff);
        $utils->shouldReceive('userCanWrite')->with($this->user, $this->project->getID())->once()->andReturnTrue();
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getGroupId')->andReturn(102);

        $webDAVFile = new WebDAVFRSFile($this->user, $this->project, new \FRSFile(['file_id' => 4]), $utils);

        $this->expectException(Forbidden::class);

        $webDAVFile->delete();
    }

    /**
     * Testing succeeded delete
     */
    public function testDeleteSucceede(): void
    {
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('delete_file')->andReturn(1);
        $utils = Mockery::mock(WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturn($frsff);
        $utils->shouldReceive('userCanWrite')->with($this->user, $this->project->getID())->once()->andReturnTrue();
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getGroupId')->andReturn(102);

        $webDAVFile = new WebDAVFRSFile($this->user, $this->project, new \FRSFile(['file_id' => 4]), $utils);

        $webDAVFile->delete();
    }
}
